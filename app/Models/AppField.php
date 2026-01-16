<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppField extends Model
{
    protected $fillable = [
        'app_entity_id',
        'name',
        'slug',
        'field_type',
        'is_nullable',
        'is_unique',
        'default_value',
        'settings',
        'sort_order',
    ];

    protected $casts = [
        'is_nullable' => 'boolean',
        'is_unique' => 'boolean',
        'settings' => 'array',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(AppEntity::class, 'app_entity_id');
    }
}
