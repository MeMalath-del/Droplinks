<?php

namespace App\Services;

use App\Models\AppVersion;
use Illuminate\Support\Facades\DB;

class AppVersionCloner
{
    public function cloneFrom(AppVersion $source, string $versionLabel, ?string $notes = null): AppVersion
    {
        return DB::transaction(function () use ($source, $versionLabel, $notes) {
            $target = $source->app->versions()->create([
                'version' => $versionLabel,
                'status' => 'draft',
                'notes' => $notes ?: 'Cloned from '.$source->version,
                'copied_from_version_id' => $source->id,
            ]);

            $entityMap = [];
            foreach ($source->entities()->with('fields')->get() as $entity) {
                $newEntity = $target->entities()->create([
                    'name' => $entity->name,
                    'slug' => $entity->slug,
                    'table_name' => $entity->table_name,
                    'description' => $entity->description,
                ]);

                $entityMap[$entity->slug] = $newEntity->id;

                foreach ($entity->fields as $field) {
                    $newEntity->fields()->create([
                        'name' => $field->name,
                        'slug' => $field->slug,
                        'field_type' => $field->field_type,
                        'is_nullable' => $field->is_nullable,
                        'is_unique' => $field->is_unique,
                        'default_value' => $field->default_value,
                        'settings' => $field->settings,
                        'sort_order' => $field->sort_order,
                    ]);
                }
            }

            $dataSourceMap = [];
            foreach ($source->dataSources()->get() as $dataSource) {
                $newSource = $target->dataSources()->create([
                    'name' => $dataSource->name,
                    'source_type' => $dataSource->source_type,
                    'config' => $this->remapEntityConfig($dataSource->config, $entityMap),
                ]);

                $dataSourceMap[$dataSource->name] = $newSource->id;
            }

            $actionMap = [];
            foreach ($source->actions()->get() as $action) {
                $newAction = $target->actions()->create([
                    'name' => $action->name,
                    'action_type' => $action->action_type,
                    'config' => $this->remapEntityConfig($action->config, $entityMap),
                ]);

                $actionMap[$action->name] = $newAction->id;
            }

            foreach ($source->workflows()->get() as $workflow) {
                $target->workflows()->create([
                    'name' => $workflow->name,
                    'definition' => $workflow->definition,
                ]);
            }

            foreach ($source->pages()->with(['components.dataSource', 'components.action', 'roles'])->get() as $page) {
                $newPage = $target->pages()->create([
                    'name' => $page->name,
                    'slug' => $page->slug,
                    'title' => $page->title,
                    'route_path' => $page->route_path,
                    'layout' => $page->layout,
                    'is_home' => $page->is_home,
                ]);

                if ($page->roles->isNotEmpty()) {
                    $roleIds = $page->roles->pluck('id')->all();
                    $newPage->roles()->sync($roleIds);
                }

                foreach ($page->components as $component) {
                    $newPage->components()->create([
                        'component_type' => $component->component_type,
                        'name' => $component->name,
                        'props' => $component->props,
                        'sort_order' => $component->sort_order,
                        'app_datasource_id' => $component->app_datasource_id
                            ? ($dataSourceMap[$component->dataSource?->name] ?? null)
                            : null,
                        'app_action_id' => $component->app_action_id
                            ? ($actionMap[$component->action?->name] ?? null)
                            : null,
                    ]);
                }
            }

            return $target;
        });
    }

    private function remapEntityConfig(?array $config, array $entityMap): ?array
    {
        if (! $config) {
            return $config;
        }

        if (isset($config['entity_slug']) && isset($entityMap[$config['entity_slug']])) {
            $config['entity_id'] = $entityMap[$config['entity_slug']];
        }

        return $config;
    }
}
