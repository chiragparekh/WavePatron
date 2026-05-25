<?php

use App\Actions\Webhook\LogWebhookActivity;
use App\Jobs\StripeWebhooks\HandleAccountUpdatedJob;
use App\Models\User;
use App\Models\WebhookCall;
use App\Support\Stripe\StripeWebhookHandler;
use Illuminate\Support\Facades\Queue;
use Spatie\Activitylog\Models\Activity;
use Spatie\StripeWebhooks\ProcessStripeWebhookJob;

beforeEach(function () {
    config([
        'stripe-webhooks.signing_secret' => 'whsec_test_secret',
        'stripe-webhooks.verify_signature' => true,
    ]);
});

test('valid stripe webhook signature is stored and queued for processing', function () {
    Queue::fake();

    $payload = stripeWebhookPayload('account.updated', [
        'id' => 'acct_test_123',
        'object' => 'account',
    ]);
    $payloadJson = json_encode($payload);

    $this->call(
        'POST',
        route('stripe.webhooks'),
        server: [
            'HTTP_Stripe-Signature' => signStripeWebhookPayload($payloadJson, 'whsec_test_secret'),
            'CONTENT_TYPE' => 'application/json',
        ],
        content: $payloadJson,
    )->assertSuccessful();

    $webhookCall = WebhookCall::query()->first();

    expect($webhookCall)->not->toBeNull()
        ->and($webhookCall->name)->toBe('stripe')
        ->and($webhookCall->stripeEventId())->toBe($payload['id'])
        ->and($webhookCall->stripeEventType())->toBe('account.updated')
        ->and($webhookCall->exception)->toBeNull();

    Queue::assertPushed(ProcessStripeWebhookJob::class);
});

test('invalid stripe webhook signature is rejected', function () {
    $payload = stripeWebhookPayload('account.updated', [
        'id' => 'acct_test_123',
        'object' => 'account',
    ]);
    $payloadJson = json_encode($payload);

    $this->call(
        'POST',
        route('stripe.webhooks'),
        server: [
            'HTTP_Stripe-Signature' => 't='.time().',v1=invalid',
            'CONTENT_TYPE' => 'application/json',
        ],
        content: $payloadJson,
    )->assertStatus(500);

    expect(WebhookCall::query()->count())->toBe(0);
});

test('duplicate stripe event ids are ignored for idempotency', function () {
    Queue::fake();

    $payload = stripeWebhookPayload('account.updated', [
        'id' => 'acct_test_123',
        'object' => 'account',
    ], eventId: 'evt_duplicate_123');

    $payloadJson = json_encode($payload);
    $headers = [
        'HTTP_Stripe-Signature' => signStripeWebhookPayload($payloadJson, 'whsec_test_secret'),
        'CONTENT_TYPE' => 'application/json',
    ];

    $this->call('POST', route('stripe.webhooks'), server: $headers, content: $payloadJson)->assertSuccessful();
    $this->call('POST', route('stripe.webhooks'), server: $headers, content: $payloadJson)->assertSuccessful();

    expect(WebhookCall::query()->count())->toBe(1);

    Queue::assertPushed(ProcessStripeWebhookJob::class, 1);
});

test('process stripe webhook job dispatches event specific jobs', function () {
    Queue::fake();

    $webhookCall = WebhookCall::query()->create([
        'name' => 'stripe',
        'url' => route('stripe.webhooks'),
        'payload' => stripeWebhookPayload('account.updated', [
            'id' => 'acct_test_123',
            'object' => 'account',
        ]),
    ]);

    (new ProcessStripeWebhookJob($webhookCall))->handle();

    Queue::assertPushed(HandleAccountUpdatedJob::class);
});

test('failed webhook job records exception and activity log entry', function () {
    $handler = Mockery::mock(StripeWebhookHandler::class);
    $handler->shouldReceive('handle')->andThrow(new RuntimeException('Processing failed'));

    $webhookCall = WebhookCall::query()->create([
        'name' => 'stripe',
        'url' => route('stripe.webhooks'),
        'payload' => stripeWebhookPayload('account.updated', [
            'id' => 'acct_test_123',
            'object' => 'account',
        ]),
    ]);

    try {
        (new HandleAccountUpdatedJob($webhookCall))->handle(
            $handler,
            app(LogWebhookActivity::class),
        );
    } catch (RuntimeException) {
        //
    }

    $webhookCall->refresh();

    expect($webhookCall->exception)->toBeArray()
        ->and($webhookCall->exceptionMessage())->toBe('Processing failed')
        ->and($webhookCall->processingStatus())->toBe('failed');

    expect(
        Activity::query()
            ->where('event', 'webhook.failed')
            ->where('subject_id', $webhookCall->id)
            ->exists()
    )->toBeTrue();
});

test('admins can browse webhook calls in filament', function () {
    $admin = User::factory()->admin()->create();

    $webhookCall = WebhookCall::query()->create([
        'name' => 'stripe',
        'url' => route('stripe.webhooks'),
        'payload' => stripeWebhookPayload('invoice.payment_succeeded', [
            'id' => 'in_test_123',
            'object' => 'invoice',
        ]),
    ]);

    $this->actingAs($admin)
        ->get('/admin/webhook-calls')
        ->assertSuccessful()
        ->assertSee('invoice.payment_succeeded')
        ->assertSee($webhookCall->stripeEventId());
});

test('admins can view failed webhook details in filament', function () {
    $admin = User::factory()->admin()->create();

    $webhookCall = WebhookCall::query()->create([
        'name' => 'stripe',
        'url' => route('stripe.webhooks'),
        'payload' => stripeWebhookPayload('account.updated', [
            'id' => 'acct_test_123',
            'object' => 'account',
        ]),
        'exception' => [
            'code' => 0,
            'message' => 'Simulated failure',
            'trace' => 'stack trace',
        ],
    ]);

    $this->actingAs($admin)
        ->get("/admin/webhook-calls/{$webhookCall->id}")
        ->assertSuccessful()
        ->assertSee('Simulated failure')
        ->assertSee('Retry processing');
});

test('retry action clears exception and requeues processing', function () {
    Queue::fake();

    $webhookCall = WebhookCall::query()->create([
        'name' => 'stripe',
        'url' => route('stripe.webhooks'),
        'payload' => stripeWebhookPayload('account.updated', [
            'id' => 'acct_test_123',
            'object' => 'account',
        ]),
        'exception' => [
            'code' => 0,
            'message' => 'Simulated failure',
            'trace' => 'stack trace',
        ],
    ]);

    $webhookCall->clearException();

    dispatch(new ProcessStripeWebhookJob($webhookCall));

    expect($webhookCall->fresh()->exception)->toBeNull();

    Queue::assertPushed(ProcessStripeWebhookJob::class);
});

/**
 * @param  array<string, mixed>  $object
 * @return array<string, mixed>
 */
function stripeWebhookPayload(string $type, array $object, ?string $eventId = null): array
{
    return [
        'id' => $eventId ?? 'evt_'.str_replace('.', '_', $type),
        'object' => 'event',
        'type' => $type,
        'data' => [
            'object' => $object,
        ],
    ];
}

function signStripeWebhookPayload(string $payload, string $secret): string
{
    $timestamp = time();
    $signedPayload = "{$timestamp}.{$payload}";
    $signature = hash_hmac('sha256', $signedPayload, $secret);

    return "t={$timestamp},v1={$signature}";
}
