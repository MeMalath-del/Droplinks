<?php

namespace App\Services;

use App\Models\AppDefinition;
use App\Models\AppWebhook;
use Illuminate\Support\Facades\Http;

class WebhookDispatcher
{
    public function dispatch(AppDefinition $app, string $event, array $payload): void
    {
        $webhooks = $app->webhooks()
            ->where('is_active', true)
            ->where(function ($query) use ($event) {
                $query->where('event', $event)
                    ->orWhere('event', '*');
            })
            ->get();

        foreach ($webhooks as $webhook) {
            $this->sendWebhook($webhook, $event, $payload);
        }
    }

    protected function sendWebhook(AppWebhook $webhook, string $event, array $payload): void
    {
        $headers = $webhook->headers ?? [];
        if ($webhook->secret) {
            $headers['X-Webhook-Secret'] = $webhook->secret;
        }

        try {
            Http::withHeaders($headers)
                ->timeout(5)
                ->post($webhook->url, [
                    'event' => $event,
                    'payload' => $payload,
                ]);
        } catch (\Throwable $exception) {
            // Intentionally swallow webhook errors.
        }
    }
}
