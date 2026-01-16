<?php

namespace App\Livewire\AppBuilder;

use App\Models\AppDefinition;
use App\Models\AppEntity;
use App\Models\AppPage;
use App\Models\AppRecord;
use App\Models\AppVersion;
use App\Services\AppMetadataImporter;
use App\Services\AppVersionCloner;
use App\Services\WorkflowEngine;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AppDetail extends Component
{
    public AppDefinition $app;
    public string $activeTab = 'overview';
    public ?int $selectedVersionId = null;

    public string $newVersionLabel = '';
    public string $newVersionNotes = '';

    public string $newEntityName = '';
    public string $newEntitySlug = '';
    public string $newEntityTable = '';
    public string $newEntityDescription = '';

    public ?int $fieldEntityId = null;
    public string $newFieldName = '';
    public string $newFieldSlug = '';
    public string $newFieldType = 'string';
    public bool $newFieldNullable = false;
    public bool $newFieldUnique = false;
    public string $newFieldDefault = '';

    public string $newPageName = '';
    public string $newPageSlug = '';
    public string $newPageTitle = '';
    public string $newPageRoute = '/';
    public bool $newPageHome = false;

    public ?int $componentPageId = null;
    public string $newComponentType = 'text';
    public string $newComponentName = '';
    public string $newComponentProps = '';
    public ?int $newComponentDataSourceId = null;
    public ?int $newComponentActionId = null;
    public int $newComponentOrder = 0;

    public string $newDataSourceName = '';
    public string $newDataSourceType = 'entity';
    public string $newDataSourceEntitySlug = '';
    public string $newDataSourceLimit = '50';
    public string $newDataSourceFilters = '';
    public string $newDataSourceStaticData = '';

    public string $newActionName = '';
    public string $newActionType = 'create_record';
    public string $newActionEntitySlug = '';
    public string $newActionWorkflow = '';

    public string $newWorkflowName = '';
    public string $newWorkflowDefinition = '';

    public string $newRoleName = '';
    public string $newRoleSlug = '';
    public string $newRoleDescription = '';
    public ?int $rolePageId = null;
    public array $roleIds = [];

    public ?int $recordEntityId = null;
    public string $recordDataJson = '';

    public string $importJson = '';
    public string $importVersionLabel = '';

    public function mount(AppDefinition $app): void
    {
        $this->app = $app;
        $this->selectedVersionId = $app->publishedVersion()?->id ?? $app->latestVersion()?->id;
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function selectVersion(int $versionId): void
    {
        $this->selectedVersionId = $versionId;
    }

    public function createVersion(AppVersionCloner $cloner): void
    {
        $versionLabel = trim($this->newVersionLabel);
        if ($versionLabel === '') {
            $next = $this->app->versions()->count() + 1;
            $versionLabel = 'v'.$next;
        }

        $source = $this->currentVersion();
        $version = $source
            ? $cloner->cloneFrom($source, $versionLabel, $this->newVersionNotes)
            : $this->app->versions()->create([
                'version' => $versionLabel,
                'status' => 'draft',
                'notes' => $this->newVersionNotes ?: 'Initial version',
            ]);

        $this->selectedVersionId = $version->id;
        $this->reset(['newVersionLabel', 'newVersionNotes']);
    }

    public function publishVersion(int $versionId): void
    {
        $version = $this->app->versions()->findOrFail($versionId);
        $version->publish();
    }

    public function createEntity(): void
    {
        $version = $this->currentVersion();
        if (! $version) {
            return;
        }

        $this->validate([
            'newEntityName' => ['required', 'string', 'max:120'],
            'newEntitySlug' => [
                'required',
                'string',
                'max:120',
                'alpha_dash',
                Rule::unique('app_entities', 'slug')->where('app_version_id', $version->id),
            ],
        ]);

        $tableName = $this->newEntityTable ?: 'entity_'.$this->newEntitySlug;

        $version->entities()->create([
            'name' => $this->newEntityName,
            'slug' => $this->newEntitySlug,
            'table_name' => $tableName,
            'description' => $this->newEntityDescription ?: null,
        ]);

        $this->reset(['newEntityName', 'newEntitySlug', 'newEntityTable', 'newEntityDescription']);
    }

    public function createField(): void
    {
        $entity = AppEntity::find($this->fieldEntityId);
        if (! $entity) {
            return;
        }

        $this->validate([
            'newFieldName' => ['required', 'string', 'max:120'],
            'newFieldSlug' => [
                'required',
                'string',
                'max:120',
                'alpha_dash',
                Rule::unique('app_fields', 'slug')->where('app_entity_id', $entity->id),
            ],
        ]);

        $entity->fields()->create([
            'name' => $this->newFieldName,
            'slug' => $this->newFieldSlug,
            'field_type' => $this->newFieldType,
            'is_nullable' => $this->newFieldNullable,
            'is_unique' => $this->newFieldUnique,
            'default_value' => $this->newFieldDefault ?: null,
            'settings' => null,
            'sort_order' => 0,
        ]);

        $this->reset([
            'newFieldName',
            'newFieldSlug',
            'newFieldType',
            'newFieldNullable',
            'newFieldUnique',
            'newFieldDefault',
        ]);
    }

    public function createPage(): void
    {
        $version = $this->currentVersion();
        if (! $version) {
            return;
        }

        $this->validate([
            'newPageName' => ['required', 'string', 'max:120'],
            'newPageSlug' => [
                'required',
                'string',
                'max:120',
                'alpha_dash',
                Rule::unique('app_pages', 'slug')->where('app_version_id', $version->id),
            ],
        ]);

        if ($this->newPageHome) {
            $version->pages()->where('is_home', true)->update(['is_home' => false]);
        }

        $version->pages()->create([
            'name' => $this->newPageName,
            'slug' => $this->newPageSlug,
            'title' => $this->newPageTitle ?: $this->newPageName,
            'route_path' => $this->newPageRoute ?: '/',
            'layout' => [
                'sections' => [],
            ],
            'is_home' => $this->newPageHome,
        ]);

        $this->reset(['newPageName', 'newPageSlug', 'newPageTitle', 'newPageRoute', 'newPageHome']);
    }

    public function createComponent(): void
    {
        $page = AppPage::find($this->componentPageId);
        if (! $page) {
            return;
        }

        $props = $this->decodeJson($this->newComponentProps);

        $page->components()->create([
            'component_type' => $this->newComponentType,
            'name' => $this->newComponentName ?: null,
            'props' => $props,
            'sort_order' => $this->newComponentOrder,
            'app_datasource_id' => $this->newComponentDataSourceId,
            'app_action_id' => $this->newComponentActionId,
        ]);

        $this->reset([
            'newComponentType',
            'newComponentName',
            'newComponentProps',
            'newComponentDataSourceId',
            'newComponentActionId',
            'newComponentOrder',
        ]);
    }

    public function createDataSource(): void
    {
        $version = $this->currentVersion();
        if (! $version) {
            return;
        }

        $this->validate([
            'newDataSourceName' => [
                'required',
                'string',
                'max:120',
                Rule::unique('app_datasources', 'name')->where('app_version_id', $version->id),
            ],
        ]);

        $config = [];
        if ($this->newDataSourceType === 'static') {
            $config['data'] = $this->decodeJson($this->newDataSourceStaticData) ?? [];
        } else {
            $config = [
                'entity_slug' => $this->newDataSourceEntitySlug ?: null,
                'limit' => (int) ($this->newDataSourceLimit ?: 50),
            ];

            $filters = $this->decodeJson($this->newDataSourceFilters);
            if ($filters) {
                $config['filters'] = $filters;
            }
        }

        $version->dataSources()->create([
            'name' => $this->newDataSourceName,
            'source_type' => $this->newDataSourceType,
            'config' => $config,
        ]);

        $this->reset([
            'newDataSourceName',
            'newDataSourceType',
            'newDataSourceEntitySlug',
            'newDataSourceLimit',
            'newDataSourceFilters',
            'newDataSourceStaticData',
        ]);
    }

    public function createAction(WorkflowEngine $workflowEngine): void
    {
        $version = $this->currentVersion();
        if (! $version) {
            return;
        }

        $this->validate([
            'newActionName' => [
                'required',
                'string',
                'max:120',
                Rule::unique('app_actions', 'name')->where('app_version_id', $version->id),
            ],
        ]);

        $config = [
            'entity_slug' => $this->newActionEntitySlug ?: null,
            'workflow_name' => $this->newActionWorkflow ?: null,
        ];

        if ($this->newActionWorkflow) {
            $workflow = $version->workflows()->where('name', $this->newActionWorkflow)->first();
            if ($workflow) {
                $config['initial_state'] = $workflowEngine->initialState($workflow);
            }
        }

        $version->actions()->create([
            'name' => $this->newActionName,
            'action_type' => $this->newActionType,
            'config' => $config,
        ]);

        $this->reset(['newActionName', 'newActionType', 'newActionEntitySlug', 'newActionWorkflow']);
    }

    public function createWorkflow(): void
    {
        $version = $this->currentVersion();
        if (! $version) {
            return;
        }

        $this->validate([
            'newWorkflowName' => [
                'required',
                'string',
                'max:120',
                Rule::unique('app_workflows', 'name')->where('app_version_id', $version->id),
            ],
        ]);

        $definition = $this->decodeJson($this->newWorkflowDefinition) ?? [
            'states' => ['draft', 'approved'],
            'transitions' => [
                ['from' => 'draft', 'to' => 'approved', 'label' => 'Approve'],
            ],
            'initial' => 'draft',
        ];

        $version->workflows()->create([
            'name' => $this->newWorkflowName,
            'definition' => $definition,
        ]);

        $this->reset(['newWorkflowName', 'newWorkflowDefinition']);
    }

    public function createRole(): void
    {
        $this->validate([
            'newRoleName' => ['required', 'string', 'max:120'],
            'newRoleSlug' => [
                'required',
                'string',
                'max:120',
                'alpha_dash',
                Rule::unique('app_roles', 'slug')->where('app_id', $this->app->id),
            ],
        ]);

        $this->app->roles()->create([
            'name' => $this->newRoleName,
            'slug' => $this->newRoleSlug,
            'description' => $this->newRoleDescription ?: null,
        ]);

        $this->reset(['newRoleName', 'newRoleSlug', 'newRoleDescription']);
    }

    public function assignRolesToPage(): void
    {
        $page = AppPage::find($this->rolePageId);
        if (! $page) {
            return;
        }

        $page->roles()->sync($this->roleIds);
        $this->reset(['rolePageId', 'roleIds']);
    }

    public function createRecord(): void
    {
        $entity = AppEntity::find($this->recordEntityId);
        if (! $entity) {
            return;
        }

        $data = $this->decodeJson($this->recordDataJson);
        if (! is_array($data)) {
            return;
        }

        $entity->records()->create([
            'data' => Arr::only($data, $entity->fields->pluck('slug')->all()),
        ]);

        $this->reset(['recordEntityId', 'recordDataJson']);
    }

    public function deleteRecord(int $recordId): void
    {
        AppRecord::where('id', $recordId)->delete();
    }

    public function importMetadata(AppMetadataImporter $importer): void
    {
        $payload = $this->decodeJson($this->importJson);
        if (! is_array($payload)) {
            return;
        }

        $label = trim($this->importVersionLabel) ?: 'v'.($this->app->versions()->count() + 1);
        $version = $importer->import($this->app, $payload, $label, 'Imported version');
        $this->selectedVersionId = $version->id;

        $this->reset(['importJson', 'importVersionLabel']);
    }

    public function updatedNewEntityName(string $value): void
    {
        if ($this->newEntitySlug === '') {
            $this->newEntitySlug = Str::slug($value);
        }

        if ($this->newEntityTable === '') {
            $this->newEntityTable = 'entity_'.Str::slug($value, '_');
        }
    }

    public function updatedNewFieldName(string $value): void
    {
        if ($this->newFieldSlug === '') {
            $this->newFieldSlug = Str::slug($value, '_');
        }
    }

    public function updatedNewRoleName(string $value): void
    {
        if ($this->newRoleSlug === '') {
            $this->newRoleSlug = Str::slug($value);
        }
    }

    protected function decodeJson(string $value): ?array
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function currentVersion(): ?AppVersion
    {
        if (! $this->selectedVersionId) {
            return null;
        }

        return $this->app->versions()->find($this->selectedVersionId);
    }

    public function render()
    {
        $versions = $this->app->versions()->latest()->get();
        $currentVersion = $this->currentVersion();

        return view('livewire.app-builder.app-detail', [
            'versions' => $versions,
            'currentVersion' => $currentVersion,
            'entities' => $currentVersion?->entities()->with('fields')->get() ?? collect(),
            'pages' => $currentVersion?->pages()->with('components')->get() ?? collect(),
            'dataSources' => $currentVersion?->dataSources()->get() ?? collect(),
            'actions' => $currentVersion?->actions()->get() ?? collect(),
            'workflows' => $currentVersion?->workflows()->get() ?? collect(),
            'roles' => $this->app->roles()->get(),
            'records' => $this->recordEntityId
                ? AppRecord::where('app_entity_id', $this->recordEntityId)->latest()->limit(50)->get()
                : collect(),
        ])->layout('layouts.app', [
            'title' => $this->app->name,
        ]);
    }
}
