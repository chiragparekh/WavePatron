<?php

namespace App\Jobs\StripeWebhooks;

use App\Actions\Webhook\LogWebhookActivity;
use App\Models\WebhookCall;
use App\Support\Stripe\StripeWebhookHandler;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;
use Throwable;

abstract class StripeWebhookJob extends ProcessWebhookJob
{
    public int $tries = 3;

    public function __construct(WebhookCall $webhookCall)
    {
        parent::__construct($webhookCall);

        $this->onConnection(config('stripe-webhooks.connection'));
        $this->onQueue(config('stripe-webhooks.queue'));
    }

    public function handle(StripeWebhookHandler $handler, LogWebhookActivity $logWebhookActivity): void
    {
        $eventType = $this->webhookCall->stripeEventType();

        if ($eventType === null) {
            return;
        }

        try {
            $handler->handle($eventType, $this->webhookCall->payload ?? []);
            $logWebhookActivity->succeeded($this->webhookCall, [
                'attempt' => $this->attempts(),
            ]);
        } catch (Throwable $exception) {
            $this->webhookCall->saveException($exception);
            $logWebhookActivity->failed($this->webhookCall, $exception, [
                'attempt' => $this->attempts(),
            ]);

            throw $exception;
        }
    }
}
