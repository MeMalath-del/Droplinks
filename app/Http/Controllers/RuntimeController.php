<?php

namespace App\Http\Controllers;

use App\Models\AppAction;
use App\Models\AppAuditLog;
use App\Models\AppDefinition;
use App\Models\AppMetric;
use App\Services\ActionRunner;
use App\Services\RuntimeRenderer;
use Illuminate\Http\Request;

class RuntimeController extends Controller
{
    public function show(Request $request, AppDefinition $app, ?string $page = null, RuntimeRenderer $renderer)
    {
        $version = $app->publishedVersion() ?? $app->latestVersion();

        if (! $version) {
            abort(404, 'No published version found.');
        }

        $pageSlug = $page ? trim($page, '/') : null;

        $pageModel = $pageSlug
            ? $version->pages()->where('slug', $pageSlug)->first()
            : $version->pages()->where('is_home', true)->first();

        if (! $pageModel) {
            abort(404, 'Page not found.');
        }

        $allowedRoles = $pageModel->roles()->pluck('slug')->all();
        $role = $request->query('role');
        if ($allowedRoles && (! $role || ! in_array($role, $allowedRoles, true))) {
            abort(403, 'Access denied.');
        }

        $context = [
            'role' => $role,
            'user_id' => $request->query('user_id'),
            'record_id' => $request->query('record_id'),
        ];

        $components = $renderer->build($pageModel, $context);

        AppMetric::create([
            'app_id' => $app->id,
            'app_version_id' => $version->id,
            'app_page_id' => $pageModel->id,
            'event_type' => 'page_view',
            'metadata' => [
                'role' => $role,
                'path' => $request->path(),
            ],
        ]);

        AppAuditLog::create([
            'app_id' => $app->id,
            'app_version_id' => $version->id,
            'app_page_id' => $pageModel->id,
            'event' => 'page.viewed',
            'actor_role' => $role,
            'ip_address' => $request->ip(),
            'metadata' => [
                'path' => $request->path(),
            ],
        ]);

        return view('runtime.page', [
            'app' => $app,
            'version' => $version,
            'page' => $pageModel,
            'components' => $components,
        ]);
    }

    public function runAction(Request $request, AppDefinition $app, AppAction $action, ActionRunner $runner)
    {
        $version = $app->publishedVersion() ?? $app->latestVersion();
        if (! $version || $action->app_version_id !== $version->id) {
            abort(404, 'Action not available.');
        }

        $payload = $request->except(['_token']);
        $files = $request->allFiles();
        $context = [
            'role' => $request->query('role'),
            'actor_id' => $request->user()?->id,
            'ip' => $request->ip(),
        ];

        $result = $runner->run($action, $payload, $files, $context);

        return back()->with('action_result', $result);
    }
}
