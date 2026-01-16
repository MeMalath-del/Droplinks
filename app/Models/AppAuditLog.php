<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppAuditLog extends Model
{
    protected $fillable = [
        'app_id',
        'app_version_id',
        'app_page_id',
        'app_action_id',
        'app_record_id',
        'event',
        'actor_role',
        'actor_id',
        'ip_address',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
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

    public function action(): BelongsTo
    {
        return $this->belongsTo(AppAction::class, 'app_action_id');
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(AppRecord::class, 'app_record_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
