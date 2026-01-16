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
        <button class="{{ $activeTab === 'datasources' ? 'active' : '' }}" wire:click="setActiveTab('datasources')">Data sources</button>
        <button class="{{ $activeTab === 'actions' ? 'active' : '' }}" wire:click="setActiveTab('actions')">Actions</button>
        <button class="{{ $activeTab === 'workflows' ? 'active' : '' }}" wire:click="setActiveTab('workflows')">Workflows</button>
        <button class="{{ $activeTab === 'roles' ? 'active' : '' }}" wire:click="setActiveTab('roles')">Roles</button>
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
                    </select>
                </label>
                <label>
                    Entity slug
                    <input type="text" wire:model="newDataSourceEntitySlug" placeholder="employees">
                </label>
                <label>
                    Limit
                    <input type="number" wire:model="newDataSourceLimit">
                </label>
                <label>
                    Filters (JSON)
                    <textarea wire:model="newDataSourceFilters" rows="2" placeholder='[{"field":"status","operator":"eq","value":"active"}]'></textarea>
                </label>
                <label>
                    Static data (JSON)
                    <textarea wire:model="newDataSourceStaticData" rows="2" placeholder='[{"name":"Sample"}]'></textarea>
                </label>
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
