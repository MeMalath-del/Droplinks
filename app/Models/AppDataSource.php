<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppDataSource extends Model
{
    protected $table = 'app_datasources';

    protected $fillable = [
        'app_version_id',
        'name',
        'source_type',
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
