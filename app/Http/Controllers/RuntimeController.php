<?php

namespace App\Http\Controllers;

use App\Models\AppDefinition;
use Illuminate\Http\Request;

class RuntimeController extends Controller
{
    public function show(Request $request, AppDefinition $app, ?string $page = null)
    {
        $version = $app->latestVersion();

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

        $components = $pageModel->components()->orderBy('sort_order')->get();

        return view('runtime.page', [
            'app' => $app,
            'version' => $version,
            'page' => $pageModel,
            'components' => $components,
        ]);
    }
}
