<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppDeployment extends Model
{
    protected $fillable = [
        'app_version_id',
        'environment',
        'status',
        'deployed_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'deployed_at' => 'datetime',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(AppVersion::class, 'app_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
