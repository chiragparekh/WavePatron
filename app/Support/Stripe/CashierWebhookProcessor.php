<?php

namespace App\Support\Stripe;

use Illuminate\Support\Str;
use Laravel\Cashier\Events\WebhookHandled;
use Laravel\Cashier\Events\WebhookReceived;
use Laravel\Cashier\Http\Controllers\WebhookController;

class CashierWebhookProcessor extends WebhookController
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function process(array $payload): void
    {
        WebhookReceived::dispatch($payload);

        $method = 'handle'.Str::studly(str_replace('.', '_', $payload['type'] ?? ''));

        if (! method_exists($this, $method)) {
            return;
        }

        $this->setMaxNetworkRetries();
        $this->{$method}($payload);

        WebhookHandled::dispatch($payload);
    }
}
