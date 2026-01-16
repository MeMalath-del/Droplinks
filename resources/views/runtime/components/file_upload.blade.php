<section class="builder-card">
    <h2>{{ $component->name ?: 'File Upload' }}</h2>

    @php
        $query = request()->only(['role', 'user_id']);
        $actionUrl = route('runtime.action', ['app' => $app->slug, 'action' => $component->action?->id]);
        if ($query) {
            $actionUrl .= '?'.http_build_query($query);
        }
    @endphp

    @if(! $component->action)
        <p class="hint">No action assigned to this uploader.</p>
    @elseif($component->action->action_type !== 'upload_file')
        <p class="hint">This uploader expects an upload_file action.</p>
    @else
        <form method="POST" enctype="multipart/form-data" action="{{ $actionUrl }}">
            @csrf
            <label>
                Select file
                <input type="file" name="file" required>
            </label>
            <label>
                Attach to record id (optional)
                <input type="number" name="record_id">
            </label>
            <button type="submit">Upload</button>
        </form>
    @endif
</section>
