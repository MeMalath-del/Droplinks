<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppEntity extends Model
{
    protected $fillable = [
        'app_version_id',
        'name',
        'slug',
        'table_name',
        'description',
    ];

    public function appVersion(): BelongsTo
    {
        return $this->belongsTo(AppVersion::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(AppField::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(AppRecord::class);
    }
}
