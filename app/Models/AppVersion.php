<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppVersion extends Model
{
    protected $fillable = [
        'app_id',
        'version',
        'status',
        'notes',
        'published_at',
        'copied_from_version_id',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(AppDefinition::class, 'app_id');
    }

    public function entities(): HasMany
    {
        return $this->hasMany(AppEntity::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(AppPage::class);
    }

    public function dataSources(): HasMany
    {
        return $this->hasMany(AppDataSource::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AppAction::class);
    }

    public function workflows(): HasMany
    {
        return $this->hasMany(AppWorkflow::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(AppDeployment::class, 'app_version_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(AppFile::class, 'app_version_id');
    }

    public function publish(): void
    {
        $this->app->versions()
            ->where('id', '!=', $this->id)
            ->where('status', 'published')
            ->update([
                'status' => 'archived',
            ]);

        $this->forceFill([
            'status' => 'published',
            'published_at' => now(),
        ])->save();

        $this->app->update([
            'status' => 'published',
        ]);
    }
}
