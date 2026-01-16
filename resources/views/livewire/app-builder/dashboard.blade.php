<div class="container">
    <header class="builder-header">
        <div>
            <h1>Droplinks Builder</h1>
            <p>Start a new app and track versions and pages.</p>
        </div>
    </header>

    <section class="builder-card">
        <h2>Create a new app</h2>

        <form wire:submit.prevent="createApp">
            <div class="grid">
                <label>
                    App name
                    <input type="text" wire:model="name" placeholder="HR system" required>
                    @error('name') <small class="error">{{ $message }}</small> @enderror
                </label>

                <label>
                    Slug
                    <input type="text" wire:model="slug" placeholder="hr-system" required>
                    @error('slug') <small class="error">{{ $message }}</small> @enderror
                </label>
            </div>

            <label>
                Short description
                <textarea wire:model="description" rows="3" placeholder="Quick summary of the app."></textarea>
                @error('description') <small class="error">{{ $message }}</small> @enderror
            </label>

            <div class="builder-actions">
                <button type="submit">Create app</button>
                <span class="hint">An initial version and home page are created automatically.</span>
            </div>
        </form>
    </section>

    <section class="builder-card">
        <h2>Existing apps</h2>

        @if($apps->isEmpty())
            <p class="hint">No apps yet. Create your first one above.</p>
        @else
            <div class="builder-grid">
                @foreach($apps as $app)
                    @php
                        $latestVersion = $app->versions->first();
                    @endphp
                    <article>
                        <h3>{{ $app->name }}</h3>
                        <p class="hint">{{ $app->description ?: 'No description yet.' }}</p>
                        <ul>
                            <li>Slug: <strong>{{ $app->slug }}</strong></li>
                            <li>Status: <strong>{{ $app->status }}</strong></li>
                            <li>Versions: <strong>{{ $app->versions_count }}</strong></li>
                            <li>Latest version: <strong>{{ $latestVersion?->version ?? 'N/A' }}</strong></li>
                        </ul>
                        <div class="builder-actions">
                            <a href="{{ url('/apps/'.$app->slug) }}">Manage app</a>
                            <a href="{{ url('/run/'.$app->slug) }}" class="hint">Run</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
