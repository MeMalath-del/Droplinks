<?php

namespace App\Livewire\AppBuilder;

use App\Models\AppComponent;
use App\Models\AppDefinition;
use App\Models\AppDeployment;
use App\Models\AppEntity;
use App\Models\AppEntityPermission;
use App\Models\AppMetric;
use App\Models\AppPage;
use App\Models\AppRecord;
use App\Models\AppVersion;
use App\Services\AppMetadataImporter;
use App\Services\AppVersionCloner;
use App\Services\WorkflowEngine;
use Livewire\WithFileUploads;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AppDetail extends Component
{
    use WithFileUploads;

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
    public array $newDataSourceFilterRows = [];
    public string $newDataSourceSortField = '';
    public string $newDataSourceSortDirection = 'asc';
    public string $newDataSourceGroupBy = '';
    public string $newDataSourceCacheTtl = '';
    public string $newDataSourceLeftEntity = '';
    public string $newDataSourceRightEntity = '';
    public string $newDataSourceLeftKey = '';
    public string $newDataSourceRightKey = '';
    public string $newDataSourceRestUrl = '';
    public string $newDataSourceRestMethod = 'GET';
    public string $newDataSourceRestHeaders = '';
    public string $newDataSourceRestQuery = '';

    public string $newActionName = '';
    public string $newActionType = 'create_record';
    public string $newActionEntitySlug = '';
    public string $newActionWorkflow = '';
    public string $newActionRestUrl = '';
    public string $newActionRestMethod = 'POST';
    public string $newActionRestHeaders = '';
    public string $newActionUploadFolder = '';

    public string $newWorkflowName = '';
    public string $newWorkflowDefinition = '';
    public array $newWorkflowTransitions = [];
    public array $newWorkflowApprovals = [];

    public string $newRoleName = '';
    public string $newRoleSlug = '';
    public string $newRoleDescription = '';
    public ?int $rolePageId = null;
    public array $roleIds = [];

    public ?int $recordEntityId = null;
    public string $recordDataJson = '';
    public $csvUpload;
    public ?int $csvEntityId = null;

    public string $importJson = '';
    public string $importVersionLabel = '';

    public ?int $builderPageId = null;
    public ?int $builderComponentId = null;
    public string $builderComponentType = 'text';
    public string $builderComponentName = '';
    public string $builderComponentProps = '';
    public ?int $builderComponentDataSourceId = null;
    public ?int $builderComponentActionId = null;
    public int $builderComponentOrder = 0;
    public string $templateType = 'crud';
    public string $templatePageName = '';
    public string $templatePageSlug = '';
    public string $templateEntitySlug = '';

    public ?int $permissionRoleId = null;
    public ?int $permissionEntityId = null;
    public bool $permissionRead = true;
    public bool $permissionWrite = true;
    public bool $permissionDelete = false;
    public string $permissionScope = 'all';

    public string $newWebhookEvent = '';
    public string $newWebhookUrl = '';
    public string $newWebhookSecret = '';
    public string $newWebhookHeaders = '';

    public string $deployEnvironment = 'dev';
    public string $deployNotes = '';

    public ?int $diffBaseVersionId = null;
    public ?int $diffCompareVersionId = null;
    public array $diffSummary = [];

    public function mount(AppDefinition $app): void
    {
        $this->app = $app;
        $this->selectedVersionId = $app->publishedVersion()?->id ?? $app->latestVersion()?->id;
        $this->builderPageId = $app->latestVersion()?->pages()->value('id');
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
        $order = $this->newComponentOrder ?: ($page->components()->max('sort_order') + 1);

        $page->components()->create([
            'component_type' => $this->newComponentType,
            'name' => $this->newComponentName ?: null,
            'props' => $props,
            'sort_order' => $order,
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

        $config = [
            'cache_ttl' => (int) ($this->newDataSourceCacheTtl ?: 0),
        ];

        if ($this->newDataSourceType === 'static') {
            $config['data'] = $this->decodeJson($this->newDataSourceStaticData) ?? [];
        } elseif ($this->newDataSourceType === 'join') {
            $config['left_entity_slug'] = $this->newDataSourceLeftEntity;
            $config['right_entity_slug'] = $this->newDataSourceRightEntity;
            $config['left_key'] = $this->newDataSourceLeftKey;
            $config['right_key'] = $this->newDataSourceRightKey;
        } elseif ($this->newDataSourceType === 'rest') {
            $config['url'] = $this->newDataSourceRestUrl;
            $config['method'] = $this->newDataSourceRestMethod;
            $config['headers'] = $this->decodeJson($this->newDataSourceRestHeaders) ?? [];
            $config['query'] = $this->decodeJson($this->newDataSourceRestQuery) ?? [];
        } else {
            $config['entity_slug'] = $this->newDataSourceEntitySlug ?: null;
            $config['limit'] = (int) ($this->newDataSourceLimit ?: 50);

            $filters = $this->normalizeFilterRows($this->newDataSourceFilterRows);
            if (! $filters) {
                $filters = $this->decodeJson($this->newDataSourceFilters) ?? [];
            }

            if ($filters) {
                $config['filters'] = $filters;
            }

            if ($this->newDataSourceSortField) {
                $config['sort'] = [
                    'field' => $this->newDataSourceSortField,
                    'direction' => $this->newDataSourceSortDirection ?: 'asc',
                ];
            }

            if ($this->newDataSourceGroupBy) {
                $config['group_by'] = $this->newDataSourceGroupBy;
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
            'newDataSourceFilterRows',
            'newDataSourceSortField',
            'newDataSourceSortDirection',
            'newDataSourceGroupBy',
            'newDataSourceCacheTtl',
            'newDataSourceLeftEntity',
            'newDataSourceRightEntity',
            'newDataSourceLeftKey',
            'newDataSourceRightKey',
            'newDataSourceRestUrl',
            'newDataSourceRestMethod',
            'newDataSourceRestHeaders',
            'newDataSourceRestQuery',
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

        if ($this->newActionType === 'rest_call') {
            $config['url'] = $this->newActionRestUrl;
            $config['method'] = $this->newActionRestMethod;
            $config['headers'] = $this->decodeJson($this->newActionRestHeaders) ?? [];
        }

        if ($this->newActionType === 'upload_file') {
            $config['folder'] = $this->newActionUploadFolder ?: null;
        }

        $version->actions()->create([
            'name' => $this->newActionName,
            'action_type' => $this->newActionType,
            'config' => $config,
        ]);

        $this->reset([
            'newActionName',
            'newActionType',
            'newActionEntitySlug',
            'newActionWorkflow',
            'newActionRestUrl',
            'newActionRestMethod',
            'newActionRestHeaders',
            'newActionUploadFolder',
        ]);
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

        $definition = $this->decodeJson($this->newWorkflowDefinition)
            ?? $this->buildWorkflowDefinition();

        $version->workflows()->create([
            'name' => $this->newWorkflowName,
            'definition' => $definition,
        ]);

        $this->reset(['newWorkflowName', 'newWorkflowDefinition', 'newWorkflowTransitions', 'newWorkflowApprovals']);
    }

    public function addWorkflowTransition(): void
    {
        $this->newWorkflowTransitions[] = [
            'from' => 'draft',
            'to' => 'approved',
            'label' => 'Approve',
            'requires_role' => '',
            'condition_field' => '',
            'condition_operator' => 'eq',
            'condition_value' => '',
            'post_action_type' => '',
            'post_action_field' => '',
            'post_action_value' => '',
        ];
    }

    public function removeWorkflowTransition(int $index): void
    {
        unset($this->newWorkflowTransitions[$index]);
        $this->newWorkflowTransitions = array_values($this->newWorkflowTransitions);
    }

    public function addWorkflowApproval(): void
    {
        $this->newWorkflowApprovals[] = [
            'state' => '',
            'role' => '',
        ];
    }

    public function removeWorkflowApproval(int $index): void
    {
        unset($this->newWorkflowApprovals[$index]);
        $this->newWorkflowApprovals = array_values($this->newWorkflowApprovals);
    }

    protected function buildWorkflowDefinition(): array
    {
        $transitions = collect($this->newWorkflowTransitions)
            ->filter(fn ($row) => ! empty($row['from']) && ! empty($row['to']))
            ->map(function ($row) {
                $transition = [
                    'from' => $row['from'],
                    'to' => $row['to'],
                    'label' => $row['label'] ?? null,
                ];

                if (! empty($row['requires_role'])) {
                    $transition['requires_role'] = $row['requires_role'];
                }

                if (! empty($row['condition_field'])) {
                    $transition['condition'] = [
                        'field' => $row['condition_field'],
                        'operator' => $row['condition_operator'] ?? 'eq',
                        'value' => $row['condition_value'] ?? null,
                    ];
                }

                if (! empty($row['post_action_type'])) {
                    $transition['post_action'] = [
                        'type' => $row['post_action_type'],
                        'field' => $row['post_action_field'] ?? null,
                        'value' => $row['post_action_value'] ?? null,
                    ];
                }

                return $transition;
            })
            ->values()
            ->all();

        $states = collect($transitions)
            ->flatMap(fn ($transition) => [$transition['from'], $transition['to']])
            ->unique()
            ->values()
            ->all();

        if (! $states) {
            $states = ['draft', 'approved'];
        }

        $approvals = collect($this->newWorkflowApprovals)
            ->filter(fn ($row) => ! empty($row['state']) && ! empty($row['role']))
            ->values()
            ->all();

        return [
            'states' => $states,
            'transitions' => $transitions ?: [
                ['from' => 'draft', 'to' => 'approved', 'label' => 'Approve'],
            ],
            'approvals' => $approvals,
            'initial' => $states[0],
        ];
    }

    public function addDataSourceFilterRow(): void
    {
        $this->newDataSourceFilterRows[] = [
            'field' => '',
            'operator' => 'eq',
            'value' => '',
        ];
    }

    public function removeDataSourceFilterRow(int $index): void
    {
        unset($this->newDataSourceFilterRows[$index]);
        $this->newDataSourceFilterRows = array_values($this->newDataSourceFilterRows);
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
            'created_by' => auth()->id(),
        ]);

        $this->reset(['recordEntityId', 'recordDataJson']);
    }

    public function importCsv(): void
    {
        $entity = AppEntity::find($this->csvEntityId);
        if (! $entity || ! $this->csvUpload) {
            return;
        }

        $path = $this->csvUpload->getRealPath();
        if (! $path) {
            return;
        }

        $handle = fopen($path, 'r');
        if (! $handle) {
            return;
        }

        $headers = fgetcsv($handle) ?: [];
        $allowed = $entity->fields->pluck('slug')->all();

        while (($row = fgetcsv($handle)) !== false) {
            $data = [];
            foreach ($headers as $index => $header) {
                $key = Str::slug($header, '_');
                if (in_array($key, $allowed, true)) {
                    $data[$key] = $row[$index] ?? null;
                }
            }

            if ($data) {
                $entity->records()->create([
                    'data' => $data,
                ]);
            }
        }

        fclose($handle);

        $this->reset(['csvUpload', 'csvEntityId']);
    }

    public function deleteRecord(int $recordId): void
    {
        AppRecord::where('id', $recordId)->delete();
    }

    public function saveEntityPermission(): void
    {
        if (! $this->permissionEntityId || ! $this->permissionRoleId) {
            return;
        }

        AppEntityPermission::updateOrCreate(
            [
                'app_entity_id' => $this->permissionEntityId,
                'app_role_id' => $this->permissionRoleId,
            ],
            [
                'can_read' => $this->permissionRead,
                'can_write' => $this->permissionWrite,
                'can_delete' => $this->permissionDelete,
                'access_scope' => $this->permissionScope,
            ]
        );

        $this->reset([
            'permissionRoleId',
            'permissionEntityId',
            'permissionRead',
            'permissionWrite',
            'permissionDelete',
            'permissionScope',
        ]);
    }

    public function createWebhook(): void
    {
        if (! $this->newWebhookEvent || ! $this->newWebhookUrl) {
            return;
        }

        $headers = $this->decodeJson($this->newWebhookHeaders) ?? [];

        $this->app->webhooks()->create([
            'event' => $this->newWebhookEvent,
            'url' => $this->newWebhookUrl,
            'secret' => $this->newWebhookSecret ?: null,
            'headers' => $headers,
            'is_active' => true,
        ]);

        $this->reset(['newWebhookEvent', 'newWebhookUrl', 'newWebhookSecret', 'newWebhookHeaders']);
    }

    public function deployVersion(int $versionId): void
    {
        $version = $this->app->versions()->find($versionId);
        if (! $version) {
            return;
        }

        AppDeployment::updateOrCreate(
            [
                'app_version_id' => $version->id,
                'environment' => $this->deployEnvironment,
            ],
            [
                'status' => 'deployed',
                'deployed_at' => now(),
                'notes' => $this->deployNotes ?: null,
                'created_by' => auth()->id(),
            ]
        );

        $this->reset(['deployEnvironment', 'deployNotes']);
    }

    public function generateVersionDiff(): void
    {
        if (! $this->diffBaseVersionId || ! $this->diffCompareVersionId) {
            return;
        }

        $base = $this->app->versions()->find($this->diffBaseVersionId);
        $compare = $this->app->versions()->find($this->diffCompareVersionId);

        if (! $base || ! $compare) {
            return;
        }

        $baseEntities = $base->entities()->pluck('slug')->all();
        $compareEntities = $compare->entities()->pluck('slug')->all();
        $basePages = $base->pages()->pluck('slug')->all();
        $comparePages = $compare->pages()->pluck('slug')->all();

        $this->diffSummary = [
            'entities_added' => array_values(array_diff($compareEntities, $baseEntities)),
            'entities_removed' => array_values(array_diff($baseEntities, $compareEntities)),
            'pages_added' => array_values(array_diff($comparePages, $basePages)),
            'pages_removed' => array_values(array_diff($basePages, $comparePages)),
            'components_delta' => $compare->pages()->withCount('components')->get()->sum('components_count')
                - $base->pages()->withCount('components')->get()->sum('components_count'),
        ];
    }

    public function selectBuilderPage(int $pageId): void
    {
        $this->builderPageId = $pageId;
        $this->builderComponentId = null;
    }

    public function loadBuilderComponent(int $componentId): void
    {
        $component = AppComponent::find($componentId);
        if (! $component) {
            return;
        }

        $this->builderComponentId = $component->id;
        $this->builderComponentType = $component->component_type;
        $this->builderComponentName = $component->name ?? '';
        $this->builderComponentProps = $component->props ? json_encode($component->props, JSON_PRETTY_PRINT) : '';
        $this->builderComponentDataSourceId = $component->app_datasource_id;
        $this->builderComponentActionId = $component->app_action_id;
        $this->builderComponentOrder = $component->sort_order;
    }

    public function saveBuilderComponent(): void
    {
        $component = AppComponent::find($this->builderComponentId);
        if (! $component) {
            return;
        }

        $component->update([
            'component_type' => $this->builderComponentType,
            'name' => $this->builderComponentName ?: null,
            'props' => $this->decodeJson($this->builderComponentProps) ?? null,
            'app_datasource_id' => $this->builderComponentDataSourceId,
            'app_action_id' => $this->builderComponentActionId,
            'sort_order' => $this->builderComponentOrder,
        ]);
    }

    public function deleteBuilderComponent(int $componentId): void
    {
        AppComponent::where('id', $componentId)->delete();
    }

    public function moveComponent(int $componentId, string $direction): void
    {
        $component = AppComponent::find($componentId);
        if (! $component) {
            return;
        }

        $order = $component->sort_order;
        $swap = $direction === 'up'
            ? AppComponent::where('app_page_id', $component->app_page_id)->where('sort_order', '<', $order)->orderBy('sort_order', 'desc')->first()
            : AppComponent::where('app_page_id', $component->app_page_id)->where('sort_order', '>', $order)->orderBy('sort_order')->first();

        if ($swap) {
            $component->update(['sort_order' => $swap->sort_order]);
            $swap->update(['sort_order' => $order]);
        }
    }

    public function applyTemplate(): void
    {
        $version = $this->currentVersion();
        if (! $version) {
            return;
        }

        $page = $this->builderPageId
            ? AppPage::find($this->builderPageId)
            : null;

        if (! $page) {
            $name = $this->templatePageName ?: ucfirst($this->templateType).' page';
            $slug = $this->templatePageSlug ?: Str::slug($name);
            $page = $version->pages()->create([
                'name' => $name,
                'slug' => $slug,
                'title' => $name,
                'route_path' => '/'.$slug,
                'layout' => ['sections' => []],
            ]);
            $this->builderPageId = $page->id;
        }

        if ($this->templateType === 'crud') {
            $this->applyCrudTemplate($page, $version);
        } elseif ($this->templateType === 'dashboard') {
            $this->applyDashboardTemplate($page, $version);
        } else {
            $this->applyReportTemplate($page, $version);
        }
    }

    protected function applyCrudTemplate(AppPage $page, AppVersion $version): void
    {
        if (! $this->templateEntitySlug) {
            return;
        }

        $source = $version->dataSources()->firstOrCreate(
            ['name' => $this->templateEntitySlug.' source'],
            [
                'source_type' => 'entity',
                'config' => [
                    'entity_slug' => $this->templateEntitySlug,
                    'limit' => 50,
                ],
            ]
        );

        $action = $version->actions()->firstOrCreate(
            ['name' => 'Create '.$this->templateEntitySlug],
            [
                'action_type' => 'create_record',
                'config' => [
                    'entity_slug' => $this->templateEntitySlug,
                ],
            ]
        );

        $page->components()->create([
            'component_type' => 'table',
            'name' => 'List',
            'props' => null,
            'sort_order' => 1,
            'app_datasource_id' => $source->id,
        ]);

        $page->components()->create([
            'component_type' => 'form',
            'name' => 'Create',
            'props' => null,
            'sort_order' => 2,
            'app_action_id' => $action->id,
        ]);

        $page->components()->create([
            'component_type' => 'record_detail',
            'name' => 'Details',
            'props' => ['record_key' => 'id'],
            'sort_order' => 3,
            'app_datasource_id' => $source->id,
        ]);
    }

    protected function applyDashboardTemplate(AppPage $page, AppVersion $version): void
    {
        if (! $this->templateEntitySlug) {
            return;
        }

        $source = $version->dataSources()->firstOrCreate(
            ['name' => $this->templateEntitySlug.' source'],
            [
                'source_type' => 'entity',
                'config' => [
                    'entity_slug' => $this->templateEntitySlug,
                    'limit' => 50,
                ],
            ]
        );

        $page->components()->create([
            'component_type' => 'kpi',
            'name' => 'Total records',
            'props' => ['metric' => 'count', 'label' => 'Total records'],
            'sort_order' => 1,
            'app_datasource_id' => $source->id,
        ]);

        $page->components()->create([
            'component_type' => 'chart',
            'name' => 'Summary',
            'props' => ['label_field' => 'id', 'value_field' => 'id'],
            'sort_order' => 2,
            'app_datasource_id' => $source->id,
        ]);

        $page->components()->create([
            'component_type' => 'table',
            'name' => 'Recent',
            'props' => null,
            'sort_order' => 3,
            'app_datasource_id' => $source->id,
        ]);
    }

    protected function applyReportTemplate(AppPage $page, AppVersion $version): void
    {
        if (! $this->templateEntitySlug) {
            return;
        }

        $source = $version->dataSources()->firstOrCreate(
            ['name' => $this->templateEntitySlug.' source'],
            [
                'source_type' => 'entity',
                'config' => [
                    'entity_slug' => $this->templateEntitySlug,
                    'limit' => 100,
                    'sort' => [
                        'field' => 'id',
                        'direction' => 'desc',
                    ],
                ],
            ]
        );

        $page->components()->create([
            'component_type' => 'timeline',
            'name' => 'Timeline',
            'props' => ['date_field' => 'created_at', 'title_field' => 'title'],
            'sort_order' => 1,
            'app_datasource_id' => $source->id,
        ]);

        $page->components()->create([
            'component_type' => 'chart',
            'name' => 'Chart',
            'props' => ['label_field' => 'id', 'value_field' => 'id'],
            'sort_order' => 2,
            'app_datasource_id' => $source->id,
        ]);
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

    protected function normalizeFilterRows(array $rows): array
    {
        return collect($rows)
            ->filter(fn ($row) => ! empty($row['field']))
            ->map(fn ($row) => [
                'field' => $row['field'],
                'operator' => $row['operator'] ?? 'eq',
                'value' => $row['value'] ?? null,
            ])
            ->values()
            ->all();
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
        $builderPage = $this->builderPageId ? AppPage::find($this->builderPageId) : null;
        $builderComponents = $builderPage
            ? $builderPage->components()->orderBy('sort_order')->get()
            : collect();

        $metrics = AppMetric::query()
            ->where('app_id', $this->app->id)
            ->latest()
            ->limit(50)
            ->get();

        $metricSummary = $metrics->groupBy('event_type')->map->count()->toArray();

        return view('livewire.app-builder.app-detail', [
            'versions' => $versions,
            'currentVersion' => $currentVersion,
            'entities' => $currentVersion?->entities()->with('fields')->get() ?? collect(),
            'pages' => $currentVersion?->pages()->with('components')->get() ?? collect(),
            'dataSources' => $currentVersion?->dataSources()->get() ?? collect(),
            'actions' => $currentVersion?->actions()->get() ?? collect(),
            'workflows' => $currentVersion?->workflows()->get() ?? collect(),
            'roles' => $this->app->roles()->get(),
            'webhooks' => $this->app->webhooks()->get(),
            'deployments' => $currentVersion?->deployments()->latest()->get() ?? collect(),
            'records' => $this->recordEntityId
                ? AppRecord::where('app_entity_id', $this->recordEntityId)->latest()->limit(50)->get()
                : collect(),
            'builderPage' => $builderPage,
            'builderComponents' => $builderComponents,
            'metrics' => $metrics,
            'metricSummary' => $metricSummary,
            'diffSummary' => $this->diffSummary,
        ])->layout('layouts.app', [
            'title' => $this->app->name,
        ]);
    }
}
