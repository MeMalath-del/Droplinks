@extends('layouts.app', ['title' => $page->title ?: $app->name])

@section('content')
    <div class="container">
        <header class="builder-header">
            <div>
                <h1>{{ $page->title ?: $page->name }}</h1>
                <p class="hint">{{ $app->name }} · version {{ $version->version }}</p>
            </div>
        </header>

        @if(session('action_result'))
            <section class="builder-card">
                <p class="hint">Action result: {{ session('action_result.status') }}</p>
                @if(session('action_result.message'))
                    <p class="hint">{{ session('action_result.message') }}</p>
                @endif
            </section>
        @endif

        @if($components->isEmpty())
            <section class="builder-card">
                <p class="hint">No components yet for this page.</p>
            </section>
        @else
            @foreach($components as $entry)
                @php
                    $component = $entry['component'];
                    $data = $entry['data'];
                    $formFields = $entry['formFields'] ?? null;
                @endphp
                @includeIf('runtime.components.'.$component->component_type, [
                    'component' => $component,
                    'data' => $data,
                    'formFields' => $formFields,
                    'record' => $record,
                    'app' => $app,
                    'version' => $version,
                ])
            @endforeach
        @endif
    </div>
@endsection
