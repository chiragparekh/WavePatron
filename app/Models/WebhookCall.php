<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\WebhookClient\Models\WebhookCall as SpatieWebhookCall;

class WebhookCall extends SpatieWebhookCall
{
    public function stripeEventId(): ?string
    {
        $id = $this->payload['id'] ?? null;

        return is_string($id) ? $id : null;
    }

    public function stripeEventType(): ?string
    {
        $type = $this->payload['type'] ?? null;

        return is_string($type) ? $type : null;
    }

    public function processingStatus(): string
    {
        return $this->exception === null ? 'succeeded' : 'failed';
    }

    public function exceptionMessage(): ?string
    {
        if (! is_array($this->exception)) {
            return null;
        }

        $message = $this->exception['message'] ?? null;

        return is_string($message) ? $message : null;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->whereNotNull('exception');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSucceeded(Builder $query): Builder
    {
        return $query->whereNull('exception');
    }
}
