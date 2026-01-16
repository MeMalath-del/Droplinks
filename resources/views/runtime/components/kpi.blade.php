<section class="builder-card">
    <h2>{{ $component->name ?: 'KPI' }}</h2>

    @if($data)
        @php
            $metric = $component->props['metric'] ?? 'count';
            $field = $component->props['field'] ?? null;
            $label = $component->props['label'] ?? ucfirst($metric);
            $values = $data->map(function ($row) use ($field) {
                return $field ? (float) ($row[$field] ?? 0) : 0;
            });
            $result = match ($metric) {
                'sum' => $values->sum(),
                'avg' => $values->count() ? round($values->avg(), 2) : 0,
                default => $data->sum('count') ?: $data->count(),
            };
        @endphp
        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
            <span class="hint">{{ $label }}</span>
            <strong style="font-size: 2rem;">{{ $result }}</strong>
        </div>
    @else
        <p class="hint">No data available.</p>
    @endif
</section>
