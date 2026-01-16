<?php

namespace App\Http\Controllers;

use App\Models\AppDefinition;
use App\Services\AppMetadataExporter;
use Illuminate\Http\Response;

class AppExportController extends Controller
{
    public function download(AppDefinition $app, AppMetadataExporter $exporter): Response
    {
        $payload = $exporter->export($app);
        $filename = $app->slug.'-metadata.json';

        return response()->streamDownload(
            function () use ($payload) {
                echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            },
            $filename,
            ['Content-Type' => 'application/json']
        );
    }
}
