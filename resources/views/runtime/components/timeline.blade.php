<section class="builder-card">
    <h2>{{ $component->name ?: 'Timeline' }}</h2>

    @if($data && $data->isNotEmpty())
        @php
            $dateField = $component->props['date_field'] ?? 'date';
            $titleField = $component->props['title_field'] ?? 'title';
            $sorted = $data->sortBy(fn ($row) => $row[$dateField] ?? null);
        @endphp
        <div style="display: grid; gap: 0.75rem;">
            @foreach($sorted as $row)
                <div style="border-left: 2px solid #e2e8f0; padding-left: 1rem;">
                    <div class="hint">{{ $row[$dateField] ?? '-' }}</div>
                    <strong>{{ $row[$titleField] ?? 'Timeline item' }}</strong>
                </div>
            @endforeach
        </div>
    @else
        <p class="hint">No timeline data.</p>
    @endif
</section>
