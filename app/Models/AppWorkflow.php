<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppWorkflow extends Model
{
    protected $table = 'app_workflows';

    protected $fillable = [
        'app_version_id',
        'name',
        'definition',
    ];

    protected $casts = [
        'definition' => 'array',
    ];

    public function appVersion(): BelongsTo
    {
        return $this->belongsTo(AppVersion::class);
    }
}
