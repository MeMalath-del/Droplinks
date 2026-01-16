<section class="builder-card">
    <h2>{{ $component->name ?: 'Table' }}</h2>

    @if($data && $data->isNotEmpty())
        @php
            $firstRow = $data->first();
            $columns = is_array($firstRow) ? array_keys($firstRow) : [];
        @endphp
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        @foreach($columns as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $row)
                        <tr>
                            @foreach($columns as $column)
                                <td>{{ $row[$column] ?? '' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="hint">No data to display.</p>
    @endif
</section>
