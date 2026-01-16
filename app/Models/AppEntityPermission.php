<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppEntityPermission extends Model
{
    protected $fillable = [
        'app_entity_id',
        'app_role_id',
        'can_read',
        'can_write',
        'can_delete',
        'access_scope',
    ];

    protected $casts = [
        'can_read' => 'boolean',
        'can_write' => 'boolean',
        'can_delete' => 'boolean',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(AppEntity::class, 'app_entity_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(AppRole::class, 'app_role_id');
    }
}
