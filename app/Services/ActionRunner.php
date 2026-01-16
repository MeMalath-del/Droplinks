<?php

namespace App\Services;

use App\Models\AppAction;
use App\Models\AppRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class ActionRunner
{
    public function __construct(private WorkflowEngine $workflowEngine)
    {
    }

    public function run(AppAction $action, array $payload): array
    {
        return match ($action->action_type) {
            'create_record' => $this->createRecord($action, $payload),
            'update_record' => $this->updateRecord($action, $payload),
            'delete_record' => $this->deleteRecord($action, $payload),
            'transition_state' => $this->transitionState($action, $payload),
            default => [
                'status' => 'error',
                'message' => 'Unknown action type.',
            ],
        };
    }

    protected function createRecord(AppAction $action, array $payload): array
    {
        $entitySlug = Arr::get($action->config ?? [], 'entity_slug');
        $entity = $this->findEntity($action, $entitySlug);

        if (! $entity) {
            return $this->error('Entity not found.');
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
            'created_by' => Auth::id(),
        ]);

        return [
            'status' => 'ok',
            'record' => $record,
        ];
    }

    protected function updateRecord(AppAction $action, array $payload): array
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
        $allowedFields = $entity->fields->pluck('slug')->all();
        $data = Arr::only($payload, $allowedFields);
        $record->update([
            'data' => array_merge($record->data ?? [], $data),
        ]);

        return [
            'status' => 'ok',
            'record' => $record,
        ];
    }

    protected function deleteRecord(AppAction $action, array $payload): array
    {
        $recordId = Arr::get($payload, 'record_id');
        if (! $recordId) {
            return $this->error('record_id is required.');
        }

        $record = AppRecord::find($recordId);
        if (! $record) {
            return $this->error('Record not found.');
        }

        $record->delete();

        return [
            'status' => 'ok',
        ];
    }

    protected function transitionState(AppAction $action, array $payload): array
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

        $workflowName = $record->workflow_name;
        if ($workflowName) {
            $workflow = $action->appVersion->workflows()->where('name', $workflowName)->first();
            if ($workflow && ! $this->workflowEngine->canTransition($workflow, $record->workflow_state ?? '', $target)) {
                return $this->error('Transition not allowed.');
            }
        }

        $record->update([
            'workflow_state' => $target,
        ]);

        return [
            'status' => 'ok',
            'record' => $record,
        ];
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
