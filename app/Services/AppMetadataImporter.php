<?php

namespace App\Services;

use App\Models\AppDefinition;
use App\Models\AppVersion;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AppMetadataImporter
{
    public function import(AppDefinition $app, array $payload, string $versionLabel, ?string $notes = null): AppVersion
    {
        return DB::transaction(function () use ($app, $payload, $versionLabel, $notes) {
            $roles = Arr::get($payload, 'roles', []);
            $roleMap = $this->syncRoles($app, $roles);

            $webhooks = Arr::get($payload, 'webhooks', []);
            $this->syncWebhooks($app, $webhooks);

            $versionPayload = Arr::get($payload, 'versions.0');
            if (! $versionPayload) {
                throw new \RuntimeException('No version payload found.');
            }

            $version = $app->versions()->create([
                'version' => $versionLabel,
                'status' => 'draft',
                'notes' => $notes ?: 'Imported metadata',
            ]);

            $entityMap = [];
            foreach (Arr::get($versionPayload, 'entities', []) as $entityData) {
                $entity = $version->entities()->create([
                    'name' => $entityData['name'],
                    'slug' => $entityData['slug'],
                    'table_name' => $entityData['table_name'] ?? $entityData['slug'],
                    'description' => $entityData['description'] ?? null,
                ]);

                $entityMap[$entity->slug] = $entity->id;

                foreach ($entityData['fields'] ?? [] as $fieldData) {
                    $entity->fields()->create([
                        'name' => $fieldData['name'],
                        'slug' => $fieldData['slug'],
                        'field_type' => $fieldData['field_type'],
                        'is_nullable' => $fieldData['is_nullable'] ?? false,
                        'is_unique' => $fieldData['is_unique'] ?? false,
                        'default_value' => $fieldData['default_value'] ?? null,
                        'settings' => $fieldData['settings'] ?? null,
                        'sort_order' => $fieldData['sort_order'] ?? 0,
                    ]);
                }

                foreach ($entityData['permissions'] ?? [] as $permissionData) {
                    $roleSlug = $permissionData['role'] ?? null;
                    if (! $roleSlug || ! isset($roleMap[$roleSlug])) {
                        continue;
                    }

                    $entity->permissions()->create([
                        'app_role_id' => $roleMap[$roleSlug],
                        'can_read' => $permissionData['can_read'] ?? true,
                        'can_write' => $permissionData['can_write'] ?? true,
                        'can_delete' => $permissionData['can_delete'] ?? false,
                        'access_scope' => $permissionData['access_scope'] ?? 'all',
                    ]);
                }
            }

            $dataSourceMap = [];
            foreach (Arr::get($versionPayload, 'data_sources', []) as $sourceData) {
                $config = $sourceData['config'] ?? [];
                if (isset($config['entity_slug']) && isset($entityMap[$config['entity_slug']])) {
                    $config['entity_id'] = $entityMap[$config['entity_slug']];
                }

                $source = $version->dataSources()->create([
                    'name' => $sourceData['name'],
                    'source_type' => $sourceData['source_type'],
                    'config' => $config,
                ]);

                $dataSourceMap[$source->name] = $source->id;
            }

            $actionMap = [];
            foreach (Arr::get($versionPayload, 'actions', []) as $actionData) {
                $config = $actionData['config'] ?? [];
                if (isset($config['entity_slug']) && isset($entityMap[$config['entity_slug']])) {
                    $config['entity_id'] = $entityMap[$config['entity_slug']];
                }

                $action = $version->actions()->create([
                    'name' => $actionData['name'],
                    'action_type' => $actionData['action_type'],
                    'config' => $config,
                ]);

                $actionMap[$action->name] = $action->id;
            }

            foreach (Arr::get($versionPayload, 'workflows', []) as $workflowData) {
                $version->workflows()->create([
                    'name' => $workflowData['name'],
                    'definition' => $workflowData['definition'] ?? null,
                ]);
            }

            foreach (Arr::get($versionPayload, 'pages', []) as $pageData) {
                $page = $version->pages()->create([
                    'name' => $pageData['name'],
                    'slug' => $pageData['slug'],
                    'title' => $pageData['title'] ?? null,
                    'route_path' => $pageData['route_path'] ?? '/',
                    'layout' => $pageData['layout'] ?? null,
                    'is_home' => $pageData['is_home'] ?? false,
                ]);

                $roleIds = collect($pageData['roles'] ?? [])
                    ->map(fn ($slug) => $roleMap[$slug] ?? null)
                    ->filter()
                    ->all();

                if ($roleIds) {
                    $page->roles()->sync($roleIds);
                }

                foreach ($pageData['components'] ?? [] as $componentData) {
                    $page->components()->create([
                        'component_type' => $componentData['component_type'],
                        'name' => $componentData['name'] ?? null,
                        'props' => $componentData['props'] ?? null,
                        'sort_order' => $componentData['sort_order'] ?? 0,
                        'app_datasource_id' => $componentData['data_source_name']
                            ? ($dataSourceMap[$componentData['data_source_name']] ?? null)
                            : null,
                        'app_action_id' => $componentData['action_name']
                            ? ($actionMap[$componentData['action_name']] ?? null)
                            : null,
                    ]);
                }
            }

            return $version;
        });
    }

    protected function syncRoles(AppDefinition $app, array $roles): array
    {
        $roleMap = [];
        foreach ($roles as $roleData) {
            $slug = $roleData['slug'] ?? null;
            if (! $slug) {
                continue;
            }

            $role = $app->roles()->firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $roleData['name'] ?? $slug,
                    'description' => $roleData['description'] ?? null,
                ]
            );

            $roleMap[$slug] = $role->id;
        }

        return $roleMap;
    }

    protected function syncWebhooks(AppDefinition $app, array $webhooks): void
    {
        foreach ($webhooks as $webhookData) {
            $url = $webhookData['url'] ?? null;
            $event = $webhookData['event'] ?? null;
            if (! $url || ! $event) {
                continue;
            }

            $app->webhooks()->updateOrCreate(
                ['event' => $event, 'url' => $url],
                [
                    'secret' => $webhookData['secret'] ?? null,
                    'headers' => $webhookData['headers'] ?? null,
                    'is_active' => $webhookData['is_active'] ?? true,
                ]
            );
        }
    }
}
