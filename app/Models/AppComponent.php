<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppComponent extends Model
{
    protected $fillable = [
        'app_page_id',
        'component_type',
        'name',
        'props',
        'sort_order',
    ];

    protected $casts = [
        'props' => 'array',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(AppPage::class, 'app_page_id');
    }
}
