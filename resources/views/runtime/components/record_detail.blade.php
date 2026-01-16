<section class="builder-card">
    <h2>{{ $component->name ?: 'Record detail' }}</h2>

    @if($record)
        @php
            $recordData = is_array($record) ? $record : (method_exists($record, 'toArray') ? $record->toArray() : []);
        @endphp
        <table>
            <tbody>
                @foreach($recordData as $key => $value)
                    <tr>
                        <th>{{ $key }}</th>
                        <td>{{ is_scalar($value) ? $value : json_encode($value) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="hint">No record selected.</p>
    @endif
</section>
