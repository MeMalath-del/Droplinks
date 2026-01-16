<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppRecord extends Model
{
    protected $fillable = [
        'app_entity_id',
        'data',
        'workflow_name',
        'workflow_state',
        'created_by',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(AppEntity::class, 'app_entity_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(AppFile::class, 'app_record_id');
    }
}
