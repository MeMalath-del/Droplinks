<?php

namespace App\Services;

use App\Models\AppDefinition;
use Illuminate\Support\Arr;

class AppMetadataExporter
{
    public function export(AppDefinition $app): array
    {
        $roles = $app->roles()->get()->map(function ($role) {
            return Arr::only($role->toArray(), ['name', 'slug', 'description']);
        })->values();

        $versions = $app->versions()
            ->with([
                'entities.fields',
                'pages.components.dataSource',
                'pages.components.action',
                'pages.roles',
                'dataSources',
                'actions',
                'workflows',
            ])
            ->get()
            ->map(function ($version) {
                return [
                    'version' => $version->version,
                    'status' => $version->status,
                    'notes' => $version->notes,
                    'published_at' => optional($version->published_at)->toIso8601String(),
                    'entities' => $version->entities->map(function ($entity) {
                        return [
                            'name' => $entity->name,
                            'slug' => $entity->slug,
                            'table_name' => $entity->table_name,
                            'description' => $entity->description,
                            'fields' => $entity->fields->map(function ($field) {
                                return [
                                    'name' => $field->name,
                                    'slug' => $field->slug,
                                    'field_type' => $field->field_type,
                                    'is_nullable' => $field->is_nullable,
                                    'is_unique' => $field->is_unique,
                                    'default_value' => $field->default_value,
                                    'settings' => $field->settings,
                                    'sort_order' => $field->sort_order,
                                ];
                            })->values(),
                        ];
                    })->values(),
                    'data_sources' => $version->dataSources->map(function ($source) {
                        return [
                            'name' => $source->name,
                            'source_type' => $source->source_type,
                            'config' => $source->config,
                        ];
                    })->values(),
                    'actions' => $version->actions->map(function ($action) {
                        return [
                            'name' => $action->name,
                            'action_type' => $action->action_type,
                            'config' => $action->config,
                        ];
                    })->values(),
                    'workflows' => $version->workflows->map(function ($workflow) {
                        return [
                            'name' => $workflow->name,
                            'definition' => $workflow->definition,
                        ];
                    })->values(),
                    'pages' => $version->pages->map(function ($page) {
                        return [
                            'name' => $page->name,
                            'slug' => $page->slug,
                            'title' => $page->title,
                            'route_path' => $page->route_path,
                            'layout' => $page->layout,
                            'is_home' => $page->is_home,
                            'roles' => $page->roles->pluck('slug')->values(),
                            'components' => $page->components->map(function ($component) {
                                return [
                                    'component_type' => $component->component_type,
                                    'name' => $component->name,
                                    'props' => $component->props,
                                    'sort_order' => $component->sort_order,
                                    'data_source_name' => $component->dataSource?->name,
                                    'action_name' => $component->action?->name,
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            })
            ->values();

        return [
            'app' => Arr::only($app->toArray(), ['name', 'slug', 'description', 'status']),
            'roles' => $roles,
            'versions' => $versions,
        ];
    }
}
