<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppRole extends Model
{
    protected $fillable = [
        'app_id',
        'name',
        'slug',
        'description',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(AppDefinition::class, 'app_id');
    }

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(AppPage::class, 'app_page_role');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'app_role_user');
    }

    public function entityPermissions(): HasMany
    {
        return $this->hasMany(AppEntityPermission::class, 'app_role_id');
    }
}
