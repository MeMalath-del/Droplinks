<section class="builder-card">
    <h2>{{ $component->name ?: 'Form' }}</h2>

    @if(! $component->action)
        <p class="hint">No action assigned to this form.</p>
    @elseif($component->action->action_type !== 'create_record')
        <p class="hint">This form expects a create_record action.</p>
    @else
        <form method="POST" action="{{ route('runtime.action', ['app' => $app->slug, 'action' => $component->action->id]) }}">
            @csrf
            @if($formFields && $formFields->isNotEmpty())
                @foreach($formFields as $field)
                    <label>
                        {{ $field->name }}
                        @switch($field->field_type)
                            @case('text')
                                <textarea name="{{ $field->slug }}" rows="2"></textarea>
                                @break
                            @case('number')
                                <input type="number" name="{{ $field->slug }}">
                                @break
                            @case('boolean')
                                <input type="checkbox" name="{{ $field->slug }}" value="1">
                                @break
                            @case('date')
                                <input type="date" name="{{ $field->slug }}">
                                @break
                            @default
                                <input type="text" name="{{ $field->slug }}">
                        @endswitch
                    </label>
                @endforeach
            @else
                <p class="hint">No fields configured for this action.</p>
            @endif
            <button type="submit">Submit</button>
        </form>
    @endif
</section>
