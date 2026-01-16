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

    public function build(AppPage $page, array $context = []): Collection
    {
        $components = $page->components()
            ->with(['dataSource', 'action'])
            ->orderBy('sort_order')
            ->get();

        return $components->map(function (AppComponent $component) use ($context) {
            return $this->buildComponent($component, $context);
        });
    }

    protected function buildComponent(AppComponent $component, array $context): array
    {
        $data = null;
        $formFields = null;
        $record = null;

        if (in_array($component->component_type, ['table', 'chart', 'kanban', 'timeline', 'record_detail', 'kpi'], true) && $component->dataSource) {
            $data = $this->resolver->resolve($component->dataSource, $context);
        }

        if ($component->component_type === 'record_detail' && $data instanceof Collection) {
            $recordId = $context['record_id'] ?? null;
            $recordKey = $component->props['record_key'] ?? 'id';
            if ($recordId) {
                $record = $data->firstWhere($recordKey, $recordId);
            } else {
                $record = $data->first();
            }
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
            'record' => $record,
        ];
    }
}
