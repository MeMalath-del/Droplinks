<?php

namespace App\Services;

use App\Models\AppComponent;
use App\Models\AppPage;
use Illuminate\Support\Collection;

class RuntimeRenderer
{
    public function __construct(private DataSourceResolver $resolver)
    {
    }

    public function build(AppPage $page): Collection
    {
        $components = $page->components()
            ->with(['dataSource', 'action'])
            ->orderBy('sort_order')
            ->get();

        return $components->map(function (AppComponent $component) {
            $data = null;
            $formFields = null;

            if (in_array($component->component_type, ['table', 'chart'], true) && $component->dataSource) {
                $data = $this->resolver->resolve($component->dataSource);
            }

            if ($component->component_type === 'form' && $component->action) {
                $entitySlug = $component->action->config['entity_slug'] ?? null;
                if ($entitySlug) {
                    $entity = $component->action->appVersion
                        ->entities()
                        ->where('slug', $entitySlug)
                        ->with('fields')
                        ->first();
                    $formFields = $entity?->fields;
                }
            }

            return [
                'component' => $component,
                'data' => $data,
                'formFields' => $formFields,
            ];
        });
    }
}
