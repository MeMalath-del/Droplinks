<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppFile extends Model
{
    protected $fillable = [
        'app_version_id',
        'app_entity_id',
        'app_record_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'created_by',
    ];

    public function appVersion(): BelongsTo
    {
        return $this->belongsTo(AppVersion::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(AppEntity::class, 'app_entity_id');
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(AppRecord::class, 'app_record_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
