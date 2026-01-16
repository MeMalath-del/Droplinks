<section class="builder-card">
    <h2>{{ $component->name ?: 'Text block' }}</h2>
    <p>{{ $component->props['content'] ?? 'No content provided.' }}</p>
</section>
