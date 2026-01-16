<?php

namespace App\Services;

use App\Models\AppDataSource;
use App\Models\AppRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class DataSourceResolver
{
    public function resolve(AppDataSource $dataSource): Collection
    {
        return match ($dataSource->source_type) {
            'static' => collect($dataSource->config['data'] ?? []),
            default => $this->resolveEntitySource($dataSource),
        };
    }

    protected function resolveEntitySource(AppDataSource $dataSource): Collection
    {
        $config = $dataSource->config ?? [];
        $entitySlug = $config['entity_slug'] ?? null;

        if (! $entitySlug) {
            return collect();
        }

        $entity = $dataSource->appVersion
            ->entities()
            ->where('slug', $entitySlug)
            ->first();

        if (! $entity) {
            return collect();
        }

        $query = AppRecord::query()
            ->where('app_entity_id', $entity->id)
            ->latest();

        $limit = (int) ($config['limit'] ?? 50);
        if ($limit > 0) {
            $query->limit(min($limit, 500));
        }

        $records = $query->get();

        $filters = $config['filters'] ?? [];
        if ($filters) {
            $records = $records->filter(function (AppRecord $record) use ($filters) {
                return $this->recordMatchesFilters($record, $filters);
            })->values();
        }

        return $records->map(function (AppRecord $record) {
            return array_merge(
                ['id' => $record->id],
                $record->data ?? [],
                [
                    'workflow_state' => $record->workflow_state,
                    'workflow_name' => $record->workflow_name,
                ]
            );
        });
    }

    protected function recordMatchesFilters(AppRecord $record, array $filters): bool
    {
        foreach ($filters as $filter) {
            $field = Arr::get($filter, 'field');
            $operator = Arr::get($filter, 'operator', 'eq');
            $value = Arr::get($filter, 'value');

            if (! $field) {
                continue;
            }

            $recordValue = Arr::get($record->data ?? [], $field);

            if (! $this->compare($recordValue, $operator, $value)) {
                return false;
            }
        }

        return true;
    }

    protected function compare($recordValue, string $operator, $value): bool
    {
        return match ($operator) {
            'contains' => is_string($recordValue) && str_contains($recordValue, (string) $value),
            'gt' => $recordValue > $value,
            'lt' => $recordValue < $value,
            'neq' => $recordValue != $value,
            default => $recordValue == $value,
        };
    }
}
