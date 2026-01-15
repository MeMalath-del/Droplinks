<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppPage extends Model
{
    protected $fillable = [
        'app_version_id',
        'name',
        'slug',
        'title',
        'route_path',
        'layout',
        'is_home',
    ];

    protected $casts = [
        'layout' => 'array',
        'is_home' => 'boolean',
    ];

    public function appVersion(): BelongsTo
    {
        return $this->belongsTo(AppVersion::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(AppComponent::class);
    }
}
