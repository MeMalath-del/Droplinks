<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppDefinition extends Model
{
    protected $table = 'apps';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(AppVersion::class, 'app_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(AppRole::class, 'app_id');
    }

    public function latestVersion(): ?AppVersion
    {
        return $this->versions()->latest('created_at')->first();
    }

    public function publishedVersion(): ?AppVersion
    {
        return $this->versions()->where('status', 'published')->latest('published_at')->first();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
