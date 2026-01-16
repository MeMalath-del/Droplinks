<?php

namespace App\Livewire\AppBuilder;

use App\Models\AppDefinition;
use App\Models\AppPage;
use Illuminate\Support\Str;
use Livewire\Component;

class Dashboard extends Component
{
    public string $name = '';
    public string $slug = '';
    public ?string $description = null;
    public bool $autoSlug = true;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', 'unique:apps,slug'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function updatedName(string $value): void
    {
        if (! $this->autoSlug) {
            return;
        }

        $this->slug = Str::slug($value);
    }

    public function updatedSlug(): void
    {
        $this->autoSlug = false;
    }

    public function createApp(): void
    {
        $validated = $this->validate();

        $app = AppDefinition::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'status' => 'draft',
        ]);

        $version = $app->versions()->create([
            'version' => 'v1',
            'status' => 'draft',
            'notes' => 'Initial version',
        ]);

        AppPage::create([
            'app_version_id' => $version->id,
            'name' => 'Home',
            'slug' => 'home',
            'title' => 'Home',
            'route_path' => '/',
            'layout' => [
                'sections' => [],
            ],
            'is_home' => true,
        ]);

        $this->reset(['name', 'slug', 'description', 'autoSlug']);
    }

    public function render()
    {
        $apps = AppDefinition::query()
            ->with(['versions' => function ($query) {
                $query->latest('created_at')->limit(1);
            }])
            ->withCount('versions')
            ->latest()
            ->get();

        return view('livewire.app-builder.dashboard', [
            'apps' => $apps,
        ])->layout('layouts.app', [
            'title' => 'Droplinks Builder',
        ]);
    }
}
