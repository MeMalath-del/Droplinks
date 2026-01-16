<section class="builder-card">
    <h2>{{ $component->name ?: 'Kanban' }}</h2>

    @php
        $groupField = $component->props['group_by'] ?? 'status';
        $groups = collect();
        if ($data && $data->isNotEmpty()) {
            if (isset($data->first()['group'])) {
                $groups = $data->map(fn ($row) => [
                    'group' => $row['group'],
                    'items' => $row['items'] ?? [],
                ]);
            } else {
                $groups = $data->groupBy($groupField)->map(function ($items, $group) {
                    return [
                        'group' => $group,
                        'items' => $items,
                    ];
                })->values();
            }
        }
    @endphp

    @if($groups->isNotEmpty())
        <div style="display: flex; gap: 1rem; overflow-x: auto;">
            @foreach($groups as $group)
                <div style="min-width: 220px;">
                    <h4>{{ $group['group'] }}</h4>
                    <div style="display: grid; gap: 0.5rem;">
                        @foreach($group['items'] as $item)
                            <div style="padding: 0.5rem; border-radius: 10px; background: #f8fafc;">
                                <strong>{{ $item['title'] ?? $item['name'] ?? 'Card' }}</strong>
                                <p class="hint">{{ $item['summary'] ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="hint">No kanban items yet.</p>
    @endif
</section>
