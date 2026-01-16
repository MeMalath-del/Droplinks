<?php

namespace App\Services;

use App\Models\AppAction;
use App\Models\AppAuditLog;
use App\Models\AppEntityPermission;
use App\Models\AppFile;
use App\Models\AppMetric;
use App\Models\AppRecord;
use App\Models\AppRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ActionRunner
{
    public function __construct(
        private WorkflowEngine $workflowEngine,
        private WebhookDispatcher $webhookDispatcher
    ) {
    }

    public function run(AppAction $action, array $payload, array $files = [], array $context = []): array
    {
        $role = $context['role'] ?? $payload['role'] ?? null;
        $actorId = $context['actor_id'] ?? Auth::id();
        $ip = $context['ip'] ?? null;

        $result = match ($action->action_type) {
            'create_record' => $this->createRecord($action, $payload, $role, $actorId),
            'update_record' => $this->updateRecord($action, $payload, $role, $actorId),
            'delete_record' => $this->deleteRecord($action, $payload, $role, $actorId),
            'transition_state' => $this->transitionState($action, $payload, $role, $actorId),
            'upload_file' => $this->uploadFile($action, $payload, $files, $role, $actorId),
            'rest_call' => $this->restCall($action, $payload),
            default => [
                'status' => 'error',
                'message' => 'Unknown action type.',
            ],
        };

        $this->logAction($action, $result, $role, $actorId, $ip);

        return $result;
    }

    protected function createRecord(AppAction $action, array $payload, ?string $role, ?int $actorId): array
    {
        $entitySlug = Arr::get($action->config ?? [], 'entity_slug');
        $entity = $this->findEntity($action, $entitySlug);

        if (! $entity) {
            return $this->error('Entity not found.');
        }

        if (! $this->permissionAllows($entity->id, $role, 'write', null, $actorId, $action->appVersion->app_id)) {
            return $this->error('Access denied.');
        }

        $allowedFields = $entity->fields->pluck('slug')->all();
        $data = Arr::only($payload, $allowedFields);

        $workflowName = Arr::get($action->config ?? [], 'workflow_name');
        $initialState = Arr::get($action->config ?? [], 'initial_state');

        if ($workflowName && ! $initialState) {
            $workflow = $action->appVersion->workflows()->where('name', $workflowName)->first();
            if ($workflow) {
                $initialState = $this->workflowEngine->initialState($workflow);
            }
        }

        $record = $entity->records()->create([
            'data' => $data,
            'workflow_name' => $workflowName,
            'workflow_state' => $initialState,
            'created_by' => $actorId,
        ]);

        $this->webhookDispatcher->dispatch(
            $action->appVersion->app,
            'record.created',
            [
                'record_id' => $record->id,
                'entity_slug' => $entitySlug,
                'data' => $record->data,
            ]
        );

        return [
            'status' => 'ok',
            'record' => $record,
        ];
    }

    protected function updateRecord(AppAction $action, array $payload, ?string $role, ?int $actorId): array
    {
        $recordId = Arr::get($payload, 'record_id');
        if (! $recordId) {
            return $this->error('record_id is required.');
        }

        $record = AppRecord::find($recordId);
        if (! $record) {
            return $this->error('Record not found.');
        }

        $entity = $record->entity;
        if (! $this->permissionAllows($entity->id, $role, 'write', $record, $actorId, $action->appVersion->app_id)) {
            return $this->error('Access denied.');
        }

        $allowedFields = $entity->fields->pluck('slug')->all();
        $data = Arr::only($payload, $allowedFields);
        $record->update([
            'data' => array_merge($record->data ?? [], $data),
        ]);

        $this->webhookDispatcher->dispatch(
            $action->appVersion->app,
            'record.updated',
            [
                'record_id' => $record->id,
                'entity_slug' => $entity->slug,
                'data' => $record->data,
            ]
        );

        return [
            'status' => 'ok',
            'record' => $record,
        ];
    }

    protected function deleteRecord(AppAction $action, array $payload, ?string $role, ?int $actorId): array
    {
        $recordId = Arr::get($payload, 'record_id');
        if (! $recordId) {
            return $this->error('record_id is required.');
        }

        $record = AppRecord::find($recordId);
        if (! $record) {
            return $this->error('Record not found.');
        }

        if (! $this->permissionAllows($record->app_entity_id, $role, 'delete', $record, $actorId, $action->appVersion->app_id)) {
            return $this->error('Access denied.');
        }

        $record->delete();

        $this->webhookDispatcher->dispatch(
            $action->appVersion->app,
            'record.deleted',
            [
                'record_id' => $recordId,
                'entity_slug' => $record->entity?->slug,
            ]
        );

        return [
            'status' => 'ok',
        ];
    }

    protected function transitionState(AppAction $action, array $payload, ?string $role, ?int $actorId): array
    {
        $recordId = Arr::get($payload, 'record_id');
        $target = Arr::get($payload, 'to_state');

        if (! $recordId || ! $target) {
            return $this->error('record_id and to_state are required.');
        }

        $record = AppRecord::find($recordId);
        if (! $record) {
            return $this->error('Record not found.');
        }

        if (! $this->permissionAllows($record->app_entity_id, $role, 'write', $record, $actorId, $action->appVersion->app_id)) {
            return $this->error('Access denied.');
        }

        $workflowName = $record->workflow_name;
        if ($workflowName) {
            $workflow = $action->appVersion->workflows()->where('name', $workflowName)->first();
            if ($workflow) {
                if (! $this->workflowEngine->canTransition($workflow, $record->workflow_state ?? '', $target, [
                    'role' => $role,
                    'record' => $record,
                ])) {
                    return $this->error('Transition not allowed.');
                }

                $transition = $this->workflowEngine->findTransition($workflow, $record->workflow_state ?? '', $target);
                $updatedData = $this->workflowEngine->applyPostAction($transition, $record->data ?? []);
                $record->update([
                    'data' => $updatedData,
                ]);
            }
        }

        $record->update([
            'workflow_state' => $target,
        ]);

        $this->webhookDispatcher->dispatch(
            $action->appVersion->app,
            'record.transitioned',
            [
                'record_id' => $record->id,
                'from' => $record->getOriginal('workflow_state'),
                'to' => $target,
                'workflow' => $workflowName,
            ]
        );

        return [
            'status' => 'ok',
            'record' => $record,
        ];
    }

    protected function uploadFile(AppAction $action, array $payload, array $files, ?string $role, ?int $actorId): array
    {
        $file = $files['file'] ?? null;
        if (! $file instanceof UploadedFile) {
            return $this->error('File is required.');
        }

        $entitySlug = Arr::get($action->config ?? [], 'entity_slug');
        $entity = $entitySlug ? $this->findEntity($action, $entitySlug) : null;
        $recordId = Arr::get($payload, 'record_id');
        $record = $recordId ? AppRecord::find($recordId) : null;

        if ($entity && ! $this->permissionAllows($entity->id, $role, 'write', $record, $actorId, $action->appVersion->app_id)) {
            return $this->error('Access denied.');
        }

        $disk = Arr::get($action->config ?? [], 'disk', 'local');
        $folder = Arr::get(
            $action->config ?? [],
            'folder',
            'uploads/'.$action->appVersion->app->slug.'/'.$action->appVersion->version
        );

        $path = $file->store($folder, $disk);

        $appFile = AppFile::create([
            'app_version_id' => $action->appVersion->id,
            'app_entity_id' => $entity?->id,
            'app_record_id' => $record?->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'created_by' => $actorId,
        ]);

        $this->webhookDispatcher->dispatch(
            $action->appVersion->app,
            'file.uploaded',
            [
                'file_id' => $appFile->id,
                'path' => $path,
                'original_name' => $appFile->original_name,
            ]
        );

        return [
            'status' => 'ok',
            'file' => $appFile,
        ];
    }

    protected function restCall(AppAction $action, array $payload): array
    {
        $url = Arr::get($action->config ?? [], 'url');
        if (! $url) {
            return $this->error('URL is required.');
        }

        $method = strtoupper(Arr::get($action->config ?? [], 'method', 'POST'));
        $headers = Arr::get($action->config ?? [], 'headers', []);

        try {
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->send($method, $url, [
                    'json' => $payload,
                ]);

            return [
                'status' => $response->successful() ? 'ok' : 'error',
                'http_status' => $response->status(),
                'response' => $response->json(),
            ];
        } catch (\Throwable $exception) {
            return [
                'status' => 'error',
                'message' => $exception->getMessage(),
            ];
        }
    }

    protected function permissionAllows(
        int $entityId,
        ?string $roleSlug,
        string $ability,
        ?AppRecord $record = null,
        ?int $actorId = null,
        ?int $appId = null
    ): bool {
        if (! $roleSlug) {
            return true;
        }

        $roleQuery = AppRole::where('slug', $roleSlug);
        if ($appId) {
            $roleQuery->where('app_id', $appId);
        }
        $role = $roleQuery->first();
        if (! $role) {
            return true;
        }

        $permission = AppEntityPermission::query()
            ->where('app_entity_id', $entityId)
            ->where('app_role_id', $role->id)
            ->first();

        if (! $permission) {
            return true;
        }

        $allowed = match ($ability) {
            'read' => $permission->can_read,
            'write' => $permission->can_write,
            'delete' => $permission->can_delete,
            default => true,
        };

        if (! $allowed) {
            return false;
        }

        if ($permission->access_scope === 'owner') {
            if (! $record || ! $actorId) {
                return false;
            }

            return $record->created_by === $actorId;
        }

        return true;
    }

    protected function logAction(AppAction $action, array $result, ?string $role, ?int $actorId, ?string $ip): void
    {
        $app = $action->appVersion->app;
        $record = $result['record'] ?? null;
        $recordId = $record instanceof AppRecord ? $record->id : ($result['record_id'] ?? null);

        AppAuditLog::create([
            'app_id' => $app->id,
            'app_version_id' => $action->appVersion->id,
            'app_action_id' => $action->id,
            'app_record_id' => $recordId,
            'event' => $result['status'] === 'ok' ? 'action.executed' : 'action.failed',
            'actor_role' => $role,
            'actor_id' => $actorId,
            'ip_address' => $ip,
            'metadata' => [
                'action_type' => $action->action_type,
                'status' => $result['status'],
                'message' => $result['message'] ?? null,
            ],
        ]);

        AppMetric::create([
            'app_id' => $app->id,
            'app_version_id' => $action->appVersion->id,
            'event_type' => 'action',
            'metadata' => [
                'action_id' => $action->id,
                'action_type' => $action->action_type,
                'status' => $result['status'],
            ],
        ]);
    }

    protected function findEntity(AppAction $action, ?string $entitySlug)
    {
        if (! $entitySlug) {
            return null;
        }

        return $action->appVersion
            ->entities()
            ->where('slug', $entitySlug)
            ->with('fields')
            ->first();
    }

    protected function error(string $message): array
    {
        return [
            'status' => 'error',
            'message' => $message,
        ];
    }
}
