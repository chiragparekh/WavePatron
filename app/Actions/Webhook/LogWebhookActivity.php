<?php

namespace App\Actions\Webhook;

use App\Actions\Activity\LogAppActivity;
use App\Models\WebhookCall;
use Throwable;

class LogWebhookActivity
{
    public function __construct(private LogAppActivity $logAppActivity) {}

    /**
     * @param  array<string, mixed>  $properties
     */
    public function succeeded(WebhookCall $webhookCall, array $properties = []): void
    {
        $this->log($webhookCall, 'webhook.processed', $properties);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public function failed(WebhookCall $webhookCall, Throwable $exception, array $properties = []): void
    {
        $this->log($webhookCall, 'webhook.failed', [
            ...$properties,
            'exception_message' => $exception->getMessage(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function log(WebhookCall $webhookCall, string $event, array $properties): void
    {
        $this->logAppActivity->execute(
            event: $event,
            subject: $webhookCall,
            properties: [
                'stripe_event_id' => $webhookCall->stripeEventId(),
                'stripe_event_type' => $webhookCall->stripeEventType(),
                ...$properties,
            ],
            logName: 'webhook',
        );
    }
}
