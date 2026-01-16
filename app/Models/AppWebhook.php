<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppWebhook extends Model
{
    protected $fillable = [
        'app_id',
        'event',
        'url',
        'secret',
        'headers',
        'is_active',
    ];

    protected $casts = [
        'headers' => 'array',
        'is_active' => 'boolean',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(AppDefinition::class, 'app_id');
    }
}
