@extends('layouts.app', ['title' => $page->title ?: $app->name])

@section('content')
    <div class="container">
        <header class="builder-header">
            <div>
                <h1>{{ $page->title ?: $page->name }}</h1>
                <p class="hint">{{ $app->name }} · version {{ $version->version }}</p>
            </div>
        </header>

        @if($components->isEmpty())
            <section class="builder-card">
                <p class="hint">No components yet for this page.</p>
            </section>
        @else
            <section class="builder-card">
                <h2>Components</h2>
                @foreach($components as $component)
                    <article>
                        <strong>{{ $component->component_type }}</strong>
                        <p class="hint">{{ $component->name ?: 'Untitled component' }}</p>
                    </article>
                @endforeach
            </section>
        @endif
    </div>
@endsection
