<div class="container">
    <header class="builder-header">
        <div>
            <h1>{{ $app->name }}</h1>
            <p class="hint">{{ $app->description ?: 'No description available.' }}</p>
        </div>
        <div class="builder-actions">
            <a href="{{ url('/run/'.$app->slug) }}" class="pill">Run app</a>
            <a href="{{ url('/apps/'.$app->slug.'/export') }}" class="pill">Export metadata</a>
            <a href="{{ url('/') }}" class="hint">Back to dashboard</a>
        </div>
    </header>

    <nav class="builder-tabs">
        <button class="{{ $activeTab === 'overview' ? 'active' : '' }}" wire:click="setActiveTab('overview')">Overview</button>
        <button class="{{ $activeTab === 'versions' ? 'active' : '' }}" wire:click="setActiveTab('versions')">Versions</button>
        <button class="{{ $activeTab === 'entities' ? 'active' : '' }}" wire:click="setActiveTab('entities')">Entities</button>
        <button class="{{ $activeTab === 'pages' ? 'active' : '' }}" wire:click="setActiveTab('pages')">Pages</button>
        <button class="{{ $activeTab === 'builder' ? 'active' : '' }}" wire:click="setActiveTab('builder')">Builder</button>
        <button class="{{ $activeTab === 'datasources' ? 'active' : '' }}" wire:click="setActiveTab('datasources')">Data sources</button>
        <button class="{{ $activeTab === 'actions' ? 'active' : '' }}" wire:click="setActiveTab('actions')">Actions</button>
        <button class="{{ $activeTab === 'workflows' ? 'active' : '' }}" wire:click="setActiveTab('workflows')">Workflows</button>
        <button class="{{ $activeTab === 'roles' ? 'active' : '' }}" wire:click="setActiveTab('roles')">Roles</button>
        <button class="{{ $activeTab === 'integrations' ? 'active' : '' }}" wire:click="setActiveTab('integrations')">Integrations</button>
        <button class="{{ $activeTab === 'records' ? 'active' : '' }}" wire:click="setActiveTab('records')">Records</button>
        <button class="{{ $activeTab === 'import' ? 'active' : '' }}" wire:click="setActiveTab('import')">Import</button>
    </nav>

    @if($activeTab === 'overview')
        <section class="builder-card">
            <h2>Current version</h2>
            @if($currentVersion)
                <p>
                    <strong>{{ $currentVersion->version }}</strong>
                    <span class="pill">{{ $currentVersion->status }}</span>
                </p>
                <p class="hint">{{ $currentVersion->notes ?: 'No notes.' }}</p>
                @if($currentVersion->published_at)
                    <p class="hint">Published at {{ $currentVersion->published_at->toDateTimeString() }}</p>
                @endif
            @else
                <p class="hint">Create a version to start building.</p>
            @endif
        </section>

        <section class="builder-card">
            <h2>Usage metrics</h2>
            @if(!empty($metricSummary))
                <ul>
                    @foreach($metricSummary as $event => $count)
                        <li>{{ $event }}: <strong>{{ $count }}</strong></li>
                    @endforeach
                </ul>
            @else
                <p class="hint">No metrics yet.</p>
            @endif
        </section>
    @endif

    @if($activeTab === 'versions')
        <section class="builder-card">
            <h2>Versions</h2>
            <div class="builder-grid">
                @foreach($versions as $version)
                    <article>
                        <h3>{{ $version->version }}</h3>
                        <p class="hint">{{ $version->notes ?: 'No notes.' }}</p>
                        <p class="hint">Status: {{ $version->status }}</p>
                        <div class="builder-actions">
                            <button wire:click="selectVersion({{ $version->id }})" type="button">Select</button>
                            @if($version->status !== 'published')
                                <button wire:click="publishVersion({{ $version->id }})" type="button">Publish</button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
        <section class="builder-card">
            <h2>Create new version</h2>
            <div class="inline-form">
                <label>
                    Version label
                    <input type="text" wire:model="newVersionLabel" placeholder="v2">
                </label>
                <label>
                    Notes
                    <textarea wire:model="newVersionNotes" rows="2"></textarea>
                </label>
                <button type="button" wire:click="createVersion">Create version</button>
            </div>
        </section>

        <section class="builder-card">
            <h2>Deploy version</h2>
            <div class="inline-form">
                <label>
                    Version
                    <select wire:model="selectedVersionId">
                        @foreach($versions as $version)
                            <option value="{{ $version->id }}">{{ $version->version }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Environment
                    <select wire:model="deployEnvironment">
                        <option value="dev">dev</option>
                        <option value="staging">staging</option>
                        <option value="prod">prod</option>
                    </select>
                </label>
                <label>
                    Notes
                    <textarea wire:model="deployNotes" rows="2"></textarea>
                </label>
                <button type="button" wire:click="deployVersion({{ $selectedVersionId ?? 0 }})">Deploy</button>
            </div>
            @if($deployments->isNotEmpty())
                <ul>
                    @foreach($deployments as $deployment)
                        <li>{{ $deployment->environment }}: {{ $deployment->status }} ({{ optional($deployment->deployed_at)->toDateTimeString() }})</li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="builder-card">
            <h2>Compare versions</h2>
            <div class="inline-form">
                <label>
                    Base
                    <select wire:model="diffBaseVersionId">
                        <option value="">Select version</option>
                        @foreach($versions as $version)
                            <option value="{{ $version->id }}">{{ $version->version }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Compare
                    <select wire:model="diffCompareVersionId">
                        <option value="">Select version</option>
                        @foreach($versions as $version)
                            <option value="{{ $version->id }}">{{ $version->version }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="button" wire:click="generateVersionDiff">Generate diff</button>
            </div>
            @if(!empty($diffSummary))
                <ul>
                    <li>Entities added: {{ implode(', ', $diffSummary['entities_added'] ?? []) ?: 'None' }}</li>
                    <li>Entities removed: {{ implode(', ', $diffSummary['entities_removed'] ?? []) ?: 'None' }}</li>
                    <li>Pages added: {{ implode(', ', $diffSummary['pages_added'] ?? []) ?: 'None' }}</li>
                    <li>Pages removed: {{ implode(', ', $diffSummary['pages_removed'] ?? []) ?: 'None' }}</li>
                    <li>Components delta: {{ $diffSummary['components_delta'] ?? 0 }}</li>
                </ul>
            @endif
        </section>
    @endif

    @if($activeTab === 'entities')
        <section class="builder-card">
            <h2>Create entity</h2>
            <div class="inline-form">
                <label>
                    Name
                    <input type="text" wire:model="newEntityName" placeholder="Employees">
                    @error('newEntityName') <small class="error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Slug
                    <input type="text" wire:model="newEntitySlug" placeholder="employees">
                    @error('newEntitySlug') <small class="error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Table name
                    <input type="text" wire:model="newEntityTable" placeholder="entity_employees">
                </label>
                <label>
                    Description
                    <textarea wire:model="newEntityDescription" rows="2"></textarea>
                </label>
                <button type="button" wire:click="createEntity">Create entity</button>
            </div>
        </section>

        <section class="builder-card">
            <h2>Entities</h2>
            @if($entities->isEmpty())
                <p class="hint">No entities yet.</p>
            @else
                <div class="builder-grid">
                    @foreach($entities as $entity)
                        <article>
                            <h3>{{ $entity->name }}</h3>
                            <p class="hint">Slug: {{ $entity->slug }}</p>
                            <p class="hint">Table: {{ $entity->table_name }}</p>
                            <ul>
                                @foreach($entity->fields as $field)
                                    <li>{{ $field->name }} <span class="hint">({{ $field->field_type }})</span></li>
                                @endforeach
                            </ul>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="builder-card">
            <h2>Add field</h2>
            <div class="inline-form">
                <label>
                    Entity
                    <select wire:model="fieldEntityId">
                        <option value="">Select entity</option>
                        @foreach($entities as $entity)
                            <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Field name
                    <input type="text" wire:model="newFieldName">
                    @error('newFieldName') <small class="error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Field slug
                    <input type="text" wire:model="newFieldSlug">
                    @error('newFieldSlug') <small class="error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Type
                    <select wire:model="newFieldType">
                        <option value="string">string</option>
                        <option value="text">text</option>
                        <option value="number">number</option>
                        <option value="boolean">boolean</option>
                        <option value="date">date</option>
                    </select>
                </label>
                <label>
                    Default value
                    <input type="text" wire:model="newFieldDefault">
                </label>
                <label>
                    <input type="checkbox" wire:model="newFieldNullable">
                    Nullable
                </label>
                <label>
                    <input type="checkbox" wire:model="newFieldUnique">
                    Unique
                </label>
                <button type="button" wire:click="createField">Add field</button>
            </div>
        </section>
    @endif

    @if($activeTab === 'pages')
        <section class="builder-card">
            <h2>Create page</h2>
            <div class="inline-form">
                <label>
                    Name
                    <input type="text" wire:model="newPageName">
                    @error('newPageName') <small class="error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Slug
                    <input type="text" wire:model="newPageSlug">
                    @error('newPageSlug') <small class="error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Title
                    <input type="text" wire:model="newPageTitle">
                </label>
                <label>
                    Route
                    <input type="text" wire:model="newPageRoute" placeholder="/">
                </label>
                <label>
                    <input type="checkbox" wire:model="newPageHome">
                    Set as home page
                </label>
                <button type="button" wire:click="createPage">Create page</button>
            </div>
        </section>

        <section class="builder-card">
            <h2>Pages</h2>
            @if($pages->isEmpty())
                <p class="hint">No pages yet.</p>
            @else
                <div class="builder-grid">
                    @foreach($pages as $page)
                        <article>
                            <h3>{{ $page->name }}</h3>
                            <p class="hint">Slug: {{ $page->slug }}</p>
                            <p class="hint">Route: {{ $page->route_path }}</p>
                            <p class="hint">Components: {{ $page->components->count() }}</p>
                            @if($page->is_home)
                                <span class="pill">Home</span>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="builder-card">
            <h2>Add component</h2>
            <div class="inline-form">
                <label>
                    Page
                    <select wire:model="componentPageId">
                        <option value="">Select page</option>
                        @foreach($pages as $page)
                            <option value="{{ $page->id }}">{{ $page->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Type
                    <select wire:model="newComponentType">
                        <option value="text">text</option>
                        <option value="table">table</option>
                        <option value="form">form</option>
                        <option value="chart">chart</option>
                        <option value="kpi">kpi</option>
                        <option value="kanban">kanban</option>
                        <option value="timeline">timeline</option>
                        <option value="file_upload">file_upload</option>
                        <option value="record_detail">record_detail</option>
                    </select>
                </label>
                <label>
                    Name
                    <input type="text" wire:model="newComponentName">
                </label>
                <label>
                    Data source
                    <select wire:model="newComponentDataSourceId">
                        <option value="">None</option>
                        @foreach($dataSources as $source)
                            <option value="{{ $source->id }}">{{ $source->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Action
                    <select wire:model="newComponentActionId">
                        <option value="">None</option>
                        @foreach($actions as $action)
                            <option value="{{ $action->id }}">{{ $action->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Props (JSON)
                    <textarea wire:model="newComponentProps" rows="3" placeholder='{"content":"Hello"}'></textarea>
                </label>
                <label>
                    Order
                    <input type="number" wire:model="newComponentOrder">
                </label>
                <button type="button" wire:click="createComponent">Add component</button>
            </div>
        </section>
    @endif

    @if($activeTab === 'builder')
        <section class="builder-card">
            <h2>Page builder</h2>
            <div class="inline-form">
                <label>
                    Page
                    <select wire:model="builderPageId">
                        <option value="">Select page</option>
                        @foreach($pages as $page)
                            <option value="{{ $page->id }}">{{ $page->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            @if($builderComponents->isNotEmpty())
                <div class="builder-grid">
                    @foreach($builderComponents as $componentItem)
                        <article>
                            <h3>{{ $componentItem->name ?: $componentItem->component_type }}</h3>
                            <p class="hint">Type: {{ $componentItem->component_type }}</p>
                            <p class="hint">Order: {{ $componentItem->sort_order }}</p>
                            <div class="builder-actions">
                                <button type="button" wire:click="loadBuilderComponent({{ $componentItem->id }})">Edit</button>
                                <button type="button" wire:click="moveComponent({{ $componentItem->id }}, 'up')">Up</button>
                                <button type="button" wire:click="moveComponent({{ $componentItem->id }}, 'down')">Down</button>
                                <button type="button" wire:click="deleteBuilderComponent({{ $componentItem->id }})">Delete</button>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="hint">Select a page to manage components.</p>
            @endif
        </section>

        <section class="builder-card">
            <h2>Edit component</h2>
            <div class="inline-form">
                <label>
                    Component type
                    <select wire:model="builderComponentType">
                        <option value="text">text</option>
                        <option value="table">table</option>
                        <option value="form">form</option>
                        <option value="chart">chart</option>
                        <option value="kpi">kpi</option>
                        <option value="kanban">kanban</option>
                        <option value="timeline">timeline</option>
                        <option value="file_upload">file_upload</option>
                        <option value="record_detail">record_detail</option>
                    </select>
                </label>
                <label>
                    Name
                    <input type="text" wire:model="builderComponentName">
                </label>
                <label>
                    Data source
                    <select wire:model="builderComponentDataSourceId">
                        <option value="">None</option>
                        @foreach($dataSources as $source)
                            <option value="{{ $source->id }}">{{ $source->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Action
                    <select wire:model="builderComponentActionId">
                        <option value="">None</option>
                        @foreach($actions as $action)
                            <option value="{{ $action->id }}">{{ $action->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Props (JSON)
                    <textarea wire:model="builderComponentProps" rows="4"></textarea>
                </label>
                <label>
                    Order
                    <input type="number" wire:model="builderComponentOrder">
                </label>
                <button type="button" wire:click="saveBuilderComponent">Save component</button>
            </div>
        </section>

        <section class="builder-card">
            <h2>Apply template</h2>
            <div class="inline-form">
                <label>
                    Template
                    <select wire:model="templateType">
                        <option value="crud">CRUD</option>
                        <option value="dashboard">Dashboard</option>
                        <option value="report">Report</option>
                    </select>
                </label>
                <label>
                    Page name
                    <input type="text" wire:model="templatePageName" placeholder="New page">
                </label>
                <label>
                    Page slug
                    <input type="text" wire:model="templatePageSlug" placeholder="new-page">
                </label>
                <label>
                    Entity slug
                    <input type="text" wire:model="templateEntitySlug" placeholder="employees">
                </label>
                <button type="button" wire:click="applyTemplate">Apply template</button>
            </div>
        </section>
    @endif

    @if($activeTab === 'datasources')
        <section class="builder-card">
            <h2>Create data source</h2>
            <div class="inline-form">
                <label>
                    Name
                    <input type="text" wire:model="newDataSourceName">
                    @error('newDataSourceName') <small class="error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Type
                    <select wire:model="newDataSourceType">
                        <option value="entity">entity</option>
                        <option value="static">static</option>
                        <option value="join">join</option>
                        <option value="rest">rest</option>
                    </select>
                </label>
                <label>
                    Cache TTL (seconds)
                    <input type="number" wire:model="newDataSourceCacheTtl" placeholder="0">
                </label>

                @if($newDataSourceType === 'entity')
                    <label>
                        Entity slug
                        <input type="text" wire:model="newDataSourceEntitySlug" placeholder="employees">
                    </label>
                    <label>
                        Limit
                        <input type="number" wire:model="newDataSourceLimit">
                    </label>
                    <label>
                        Sort field
                        <input type="text" wire:model="newDataSourceSortField" placeholder="created_at">
                    </label>
                    <label>
                        Sort direction
                        <select wire:model="newDataSourceSortDirection">
                            <option value="asc">asc</option>
                            <option value="desc">desc</option>
                        </select>
                    </label>
                    <label>
                        Group by
                        <input type="text" wire:model="newDataSourceGroupBy" placeholder="status">
                    </label>

                    <div>
                        <strong>Filters</strong>
                        <div class="inline-form">
                            @foreach($newDataSourceFilterRows as $index => $filter)
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <input type="text" wire:model="newDataSourceFilterRows.{{ $index }}.field" placeholder="field">
                                    <select wire:model="newDataSourceFilterRows.{{ $index }}.operator">
                                        <option value="eq">eq</option>
                                        <option value="neq">neq</option>
                                        <option value="contains">contains</option>
                                        <option value="gt">gt</option>
                                        <option value="lt">lt</option>
                                    </select>
                                    <input type="text" wire:model="newDataSourceFilterRows.{{ $index }}.value" placeholder="value">
                                    <button type="button" wire:click="removeDataSourceFilterRow({{ $index }})">Remove</button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" wire:click="addDataSourceFilterRow">Add filter</button>
                        <label>
                            Filters (JSON fallback)
                            <textarea wire:model="newDataSourceFilters" rows="2" placeholder='[{"field":"status","operator":"eq","value":"active"}]'></textarea>
                        </label>
                    </div>
                @endif

                @if($newDataSourceType === 'static')
                    <label>
                        Static data (JSON)
                        <textarea wire:model="newDataSourceStaticData" rows="2" placeholder='[{"name":"Sample"}]'></textarea>
                    </label>
                @endif

                @if($newDataSourceType === 'join')
                    <label>
                        Left entity slug
                        <input type="text" wire:model="newDataSourceLeftEntity" placeholder="orders">
                    </label>
                    <label>
                        Right entity slug
                        <input type="text" wire:model="newDataSourceRightEntity" placeholder="customers">
                    </label>
                    <label>
                        Left key
                        <input type="text" wire:model="newDataSourceLeftKey" placeholder="customer_id">
                    </label>
                    <label>
                        Right key
                        <input type="text" wire:model="newDataSourceRightKey" placeholder="id">
                    </label>
                @endif

                @if($newDataSourceType === 'rest')
                    <label>
                        URL
                        <input type="text" wire:model="newDataSourceRestUrl" placeholder="https://api.example.com/data">
                    </label>
                    <label>
                        Method
                        <select wire:model="newDataSourceRestMethod">
                            <option value="GET">GET</option>
                            <option value="POST">POST</option>
                        </select>
                    </label>
                    <label>
                        Headers (JSON)
                        <textarea wire:model="newDataSourceRestHeaders" rows="2" placeholder='{"Authorization":"Bearer token"}'></textarea>
                    </label>
                    <label>
                        Query params (JSON)
                        <textarea wire:model="newDataSourceRestQuery" rows="2" placeholder='{"limit":10}'></textarea>
                    </label>
                @endif

                <button type="button" wire:click="createDataSource">Create data source</button>
            </div>
        </section>

        <section class="builder-card">
            <h2>Data sources</h2>
            @if($dataSources->isEmpty())
                <p class="hint">No data sources yet.</p>
            @else
                <div class="builder-grid">
                    @foreach($dataSources as $source)
                        <article>
                            <h3>{{ $source->name }}</h3>
                            <p class="hint">Type: {{ $source->source_type }}</p>
                            <p class="hint">Entity: {{ data_get($source->config, 'entity_slug', 'N/A') }}</p>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    @if($activeTab === 'actions')
        <section class="builder-card">
            <h2>Create action</h2>
            <div class="inline-form">
                <label>
                    Name
                    <input type="text" wire:model="newActionName">
                    @error('newActionName') <small class="error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Type
                    <select wire:model="newActionType">
                        <option value="create_record">create_record</option>
                        <option value="update_record">update_record</option>
                        <option value="delete_record">delete_record</option>
                        <option value="transition_state">transition_state</option>
                        <option value="upload_file">upload_file</option>
                        <option value="rest_call">rest_call</option>
                    </select>
                </label>
                <label>
                    Entity slug
                    <input type="text" wire:model="newActionEntitySlug">
                </label>
                <label>
                    Workflow name
                    <input type="text" wire:model="newActionWorkflow">
                </label>
                @if($newActionType === 'rest_call')
                    <label>
                        URL
                        <input type="text" wire:model="newActionRestUrl" placeholder="https://api.example.com">
                    </label>
                    <label>
                        Method
                        <select wire:model="newActionRestMethod">
                            <option value="POST">POST</option>
                            <option value="GET">GET</option>
                        </select>
                    </label>
                    <label>
                        Headers (JSON)
                        <textarea wire:model="newActionRestHeaders" rows="2"></textarea>
                    </label>
                @endif
                @if($newActionType === 'upload_file')
                    <label>
                        Upload folder
                        <input type="text" wire:model="newActionUploadFolder" placeholder="uploads/hr">
                    </label>
                @endif
                <button type="button" wire:click="createAction">Create action</button>
            </div>
        </section>

        <section class="builder-card">
            <h2>Actions</h2>
            @if($actions->isEmpty())
                <p class="hint">No actions yet.</p>
            @else
                <div class="builder-grid">
                    @foreach($actions as $action)
                        <article>
                            <h3>{{ $action->name }}</h3>
                            <p class="hint">Type: {{ $action->action_type }}</p>
                            <p class="hint">Entity: {{ data_get($action->config, 'entity_slug', 'N/A') }}</p>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    @if($activeTab === 'workflows')
        <section class="builder-card">
            <h2>Create workflow</h2>
            <div class="inline-form">
                <label>
                    Name
                    <input type="text" wire:model="newWorkflowName">
                    @error('newWorkflowName') <small class="error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Definition (JSON)
                    <textarea wire:model="newWorkflowDefinition" rows="4" placeholder='{"states":["draft","approved"],"transitions":[{"from":"draft","to":"approved"}],"initial":"draft"}'></textarea>
                </label>
                <div>
                    <strong>Transitions</strong>
                    @foreach($newWorkflowTransitions as $index => $transition)
                        <div style="display: grid; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <input type="text" wire:model="newWorkflowTransitions.{{ $index }}.from" placeholder="from">
                            <input type="text" wire:model="newWorkflowTransitions.{{ $index }}.to" placeholder="to">
                            <input type="text" wire:model="newWorkflowTransitions.{{ $index }}.label" placeholder="label">
                            <input type="text" wire:model="newWorkflowTransitions.{{ $index }}.requires_role" placeholder="requires role">
                            <input type="text" wire:model="newWorkflowTransitions.{{ $index }}.condition_field" placeholder="condition field">
                            <select wire:model="newWorkflowTransitions.{{ $index }}.condition_operator">
                                <option value="eq">eq</option>
                                <option value="neq">neq</option>
                                <option value="contains">contains</option>
                                <option value="gt">gt</option>
                                <option value="lt">lt</option>
                            </select>
                            <input type="text" wire:model="newWorkflowTransitions.{{ $index }}.condition_value" placeholder="condition value">
                            <input type="text" wire:model="newWorkflowTransitions.{{ $index }}.post_action_type" placeholder="post_action type">
                            <input type="text" wire:model="newWorkflowTransitions.{{ $index }}.post_action_field" placeholder="post_action field">
                            <input type="text" wire:model="newWorkflowTransitions.{{ $index }}.post_action_value" placeholder="post_action value">
                            <button type="button" wire:click="removeWorkflowTransition({{ $index }})">Remove transition</button>
                        </div>
                    @endforeach
                    <button type="button" wire:click="addWorkflowTransition">Add transition</button>
                </div>
                <div>
                    <strong>Approvals</strong>
                    @foreach($newWorkflowApprovals as $index => $approval)
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="text" wire:model="newWorkflowApprovals.{{ $index }}.state" placeholder="state">
                            <input type="text" wire:model="newWorkflowApprovals.{{ $index }}.role" placeholder="role">
                            <button type="button" wire:click="removeWorkflowApproval({{ $index }})">Remove</button>
                        </div>
                    @endforeach
                    <button type="button" wire:click="addWorkflowApproval">Add approval</button>
                </div>
                <button type="button" wire:click="createWorkflow">Create workflow</button>
            </div>
        </section>

        <section class="builder-card">
            <h2>Workflows</h2>
            @if($workflows->isEmpty())
                <p class="hint">No workflows yet.</p>
            @else
                <div class="builder-grid">
                    @foreach($workflows as $workflow)
                        <article>
                            <h3>{{ $workflow->name }}</h3>
                            <p class="hint">States: {{ implode(', ', data_get($workflow->definition, 'states', [])) }}</p>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    @if($activeTab === 'roles')
        <section class="builder-card">
            <h2>Create role</h2>
            <div class="inline-form">
                <label>
                    Name
                    <input type="text" wire:model="newRoleName">
                    @error('newRoleName') <small class="error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Slug
                    <input type="text" wire:model="newRoleSlug">
                    @error('newRoleSlug') <small class="error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Description
                    <textarea wire:model="newRoleDescription" rows="2"></textarea>
                </label>
                <button type="button" wire:click="createRole">Create role</button>
            </div>
        </section>

        <section class="builder-card">
            <h2>Assign roles to page</h2>
            <div class="inline-form">
                <label>
                    Page
                    <select wire:model="rolePageId">
                        <option value="">Select page</option>
                        @foreach($pages as $page)
                            <option value="{{ $page->id }}">{{ $page->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Roles
                    <select wire:model="roleIds" multiple>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="button" wire:click="assignRolesToPage">Save access</button>
            </div>
        </section>

        <section class="builder-card">
            <h2>Entity permissions</h2>
            <div class="inline-form">
                <label>
                    Role
                    <select wire:model="permissionRoleId">
                        <option value="">Select role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Entity
                    <select wire:model="permissionEntityId">
                        <option value="">Select entity</option>
                        @foreach($entities as $entity)
                            <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <input type="checkbox" wire:model="permissionRead">
                    Read
                </label>
                <label>
                    <input type="checkbox" wire:model="permissionWrite">
                    Write
                </label>
                <label>
                    <input type="checkbox" wire:model="permissionDelete">
                    Delete
                </label>
                <label>
                    Scope
                    <select wire:model="permissionScope">
                        <option value="all">all</option>
                        <option value="owner">owner</option>
                    </select>
                </label>
                <button type="button" wire:click="saveEntityPermission">Save permission</button>
            </div>
        </section>

        <section class="builder-card">
            <h2>Roles</h2>
            @if($roles->isEmpty())
                <p class="hint">No roles defined.</p>
            @else
                <div class="builder-grid">
                    @foreach($roles as $role)
                        <article>
                            <h3>{{ $role->name }}</h3>
                            <p class="hint">{{ $role->slug }}</p>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    @if($activeTab === 'integrations')
        <section class="builder-card">
            <h2>Webhooks</h2>
            <div class="inline-form">
                <label>
                    Event
                    <input type="text" wire:model="newWebhookEvent" placeholder="record.created">
                </label>
                <label>
                    URL
                    <input type="text" wire:model="newWebhookUrl" placeholder="https://example.com/webhook">
                </label>
                <label>
                    Secret
                    <input type="text" wire:model="newWebhookSecret">
                </label>
                <label>
                    Headers (JSON)
                    <textarea wire:model="newWebhookHeaders" rows="2"></textarea>
                </label>
                <button type="button" wire:click="createWebhook">Create webhook</button>
            </div>
            @if($webhooks->isNotEmpty())
                <ul>
                    @foreach($webhooks as $webhook)
                        <li>{{ $webhook->event }} → {{ $webhook->url }}</li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

    @if($activeTab === 'records')
        <section class="builder-card">
            <h2>Create record</h2>
            <div class="inline-form">
                <label>
                    Entity
                    <select wire:model="recordEntityId">
                        <option value="">Select entity</option>
                        @foreach($entities as $entity)
                            <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Record data (JSON)
                    <textarea wire:model="recordDataJson" rows="3" placeholder='{"name":"Jane"}'></textarea>
                </label>
                <button type="button" wire:click="createRecord">Create record</button>
            </div>
        </section>

        <section class="builder-card">
            <h2>Import CSV</h2>
            <div class="inline-form">
                <label>
                    Entity
                    <select wire:model="csvEntityId">
                        <option value="">Select entity</option>
                        @foreach($entities as $entity)
                            <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    CSV file
                    <input type="file" wire:model="csvUpload">
                </label>
                <button type="button" wire:click="importCsv">Import CSV</button>
                <p class="hint">CSV headers should match field slugs.</p>
            </div>
        </section>

        <section class="builder-card">
            <h2>Recent records</h2>
            @if($records->isEmpty())
                <p class="hint">Select an entity to preview records.</p>
            @else
                <div class="builder-grid">
                    @foreach($records as $record)
                        <article>
                            <p class="hint">#{{ $record->id }}</p>
                            <pre>{{ json_encode($record->data, JSON_PRETTY_PRINT) }}</pre>
                            <button type="button" wire:click="deleteRecord({{ $record->id }})">Delete</button>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    @if($activeTab === 'import')
        <section class="builder-card">
            <h2>Import metadata</h2>
            <div class="inline-form">
                <label>
                    Version label
                    <input type="text" wire:model="importVersionLabel" placeholder="v2">
                </label>
                <label>
                    JSON payload
                    <textarea wire:model="importJson" rows="6" placeholder='{"app": {...}, "versions": [...]}'
                    ></textarea>
                </label>
                <button type="button" wire:click="importMetadata">Import</button>
                <p class="hint">Imports the first version in the JSON payload.</p>
            </div>
        </section>
    @endif
</div>
