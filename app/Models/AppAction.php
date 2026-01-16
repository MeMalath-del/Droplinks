<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppAction extends Model
{
    protected $table = 'app_actions';

    protected $fillable = [
        'app_version_id',
        'name',
        'action_type',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function appVersion(): BelongsTo
    {
        return $this->belongsTo(AppVersion::class);
    }
}
