<section class="builder-card">
    <h2>{{ $component->name ?: 'Chart' }}</h2>

    @if($data && $data->isNotEmpty())
        @php
            $labelField = $component->props['label_field'] ?? 'label';
            $valueField = $component->props['value_field'] ?? 'value';
            $values = $data->map(function ($row) use ($valueField) {
                if (isset($row['count']) && $valueField === 'value') {
                    return (float) $row['count'];
                }
                return (float) ($row[$valueField] ?? 0);
            });
            $max = max($values->toArray()) ?: 1;
        @endphp
        <div>
            @foreach($data as $row)
                @php
                    $label = $row[$labelField] ?? $row['group'] ?? 'Item';
                    $value = (float) ($row[$valueField] ?? $row['count'] ?? 0);
                    $width = round(($value / $max) * 100);
                @endphp
                <div style="margin-bottom: 0.6rem;">
                    <div class="hint">{{ $label }} ({{ $value }})</div>
                    <div style="background: #e2e8f0; height: 8px; border-radius: 6px;">
                        <div style="width: {{ $width }}%; height: 8px; border-radius: 6px; background: #0ea5e9;"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="hint">No data to display.</p>
    @endif
</section>
