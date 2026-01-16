<?php

namespace App\Services;

use App\Models\AppDataSource;
use App\Models\AppEntityPermission;
use App\Models\AppRecord;
use App\Models\AppRole;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;

class DataSourceResolver
{
    public function resolve(AppDataSource $dataSource, array $context = []): Collection
    {
        $cacheTtl = (int) Arr::get($dataSource->config ?? [], 'cache_ttl', 0);
        if ($cacheTtl > 0) {
            $cacheKey = sprintf(
                'datasource:%d:%s:%s',
                $dataSource->id,
                $context['role'] ?? 'anon',
                $context['user_id'] ?? 'guest'
            );

            return Cache::remember($cacheKey, $cacheTtl, function () use ($dataSource, $context) {
                return $this->resolveInternal($dataSource, $context);
            });
        }

        return $this->resolveInternal($dataSource, $context);
    }

    protected function resolveInternal(AppDataSource $dataSource, array $context): Collection
    {
        return match ($dataSource->source_type) {
            'static' => collect($dataSource->config['data'] ?? []),
            'rest' => $this->resolveRestSource($dataSource),
            'join' => $this->resolveJoinSource($dataSource, $context),
            default => $this->resolveEntitySource($dataSource, $context),
        };
    }

    protected function resolveEntitySource(AppDataSource $dataSource, array $context): Collection
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

        $permission = $this->resolvePermission($entity->id, $context, $dataSource->appVersion->app_id);
        if ($permission && ! $permission->can_read) {
            return collect();
        }

        $query = AppRecord::query()
            ->where('app_entity_id', $entity->id)
            ->latest();

        if ($permission && $permission->access_scope === 'owner') {
            $userId = $context['user_id'] ?? null;
            if (! $userId) {
                return collect();
            }
            $query->where('created_by', $userId);
        }

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

        $sort = $config['sort'] ?? null;
        if (is_array($sort) && isset($sort['field'])) {
            $direction = strtolower($sort['direction'] ?? 'asc');
            $records = $records->sortBy(function (AppRecord $record) use ($sort) {
                return Arr::get($record->data ?? [], $sort['field']);
            }, SORT_REGULAR, $direction === 'desc')->values();
        }

        $mapped = $records->map(function (AppRecord $record) {
            return array_merge(
                ['id' => $record->id],
                $record->data ?? [],
                [
                    'workflow_state' => $record->workflow_state,
                    'workflow_name' => $record->workflow_name,
                    'created_at' => optional($record->created_at)->toDateTimeString(),
                    'updated_at' => optional($record->updated_at)->toDateTimeString(),
                ]
            );
        });

        $groupBy = $config['group_by'] ?? null;
        if ($groupBy) {
            return $mapped->groupBy($groupBy)->map(function (Collection $group, $key) {
                return [
                    'group' => $key,
                    'count' => $group->count(),
                    'items' => $group->values(),
                ];
            })->values();
        }

        return $mapped;
    }

    protected function resolveJoinSource(AppDataSource $dataSource, array $context): Collection
    {
        $config = $dataSource->config ?? [];
        $leftSlug = $config['left_entity_slug'] ?? null;
        $rightSlug = $config['right_entity_slug'] ?? null;
        $leftKey = $config['left_key'] ?? null;
        $rightKey = $config['right_key'] ?? null;

        if (! $leftSlug || ! $rightSlug || ! $leftKey || ! $rightKey) {
            return collect();
        }

        $leftSource = $dataSource->replicate(['id']);
        $leftSource->source_type = 'entity';
        $leftSource->config = array_merge($config, ['entity_slug' => $leftSlug]);

        $rightSource = $dataSource->replicate(['id']);
        $rightSource->source_type = 'entity';
        $rightSource->config = array_merge($config, ['entity_slug' => $rightSlug]);

        $leftRecords = $this->resolveEntitySource($leftSource, $context);
        $rightRecords = $this->resolveEntitySource($rightSource, $context)->keyBy($rightKey);

        return $leftRecords->map(function (array $left) use ($rightRecords, $leftKey) {
            $right = $rightRecords->get($left[$leftKey] ?? null);

            return array_merge(
                $this->prefixKeys($left, 'left_'),
                $right ? $this->prefixKeys($right, 'right_') : []
            );
        })->values();
    }

    protected function resolveRestSource(AppDataSource $dataSource): Collection
    {
        $config = $dataSource->config ?? [];
        $url = $config['url'] ?? null;

        if (! $url) {
            return collect();
        }

        $method = strtoupper($config['method'] ?? 'GET');
        $headers = $config['headers'] ?? [];
        $query = $config['query'] ?? [];

        try {
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->send($method, $url, [
                    'query' => $query,
                ]);

            $data = $response->json();
            return collect(is_array($data) ? $data : []);
        } catch (\Throwable $exception) {
            return collect();
        }
    }

    protected function resolvePermission(int $entityId, array $context, ?int $appId): ?AppEntityPermission
    {
        $roleSlug = $context['role'] ?? null;
        if (! $roleSlug) {
            return null;
        }

        $roleQuery = AppRole::where('slug', $roleSlug);
        if ($appId) {
            $roleQuery->where('app_id', $appId);
        }
        $role = $roleQuery->first();
        if (! $role) {
            return null;
        }

        return AppEntityPermission::query()
            ->where('app_entity_id', $entityId)
            ->where('app_role_id', $role->id)
            ->first();
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

    protected function prefixKeys(array $data, string $prefix): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[$prefix.$key] = $value;
        }

        return $result;
    }
}
