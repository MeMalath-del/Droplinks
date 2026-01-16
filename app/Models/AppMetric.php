<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppMetric extends Model
{
    protected $fillable = [
        'app_id',
        'app_version_id',
        'app_page_id',
        'event_type',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(AppDefinition::class, 'app_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(AppVersion::class, 'app_version_id');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(AppPage::class, 'app_page_id');
    }
}
