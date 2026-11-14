<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\DueStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PaymentWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.simupay.webhook_secret' => 'whsec_test']);
    }

    /**
     * Envoie un webhook comme le ferait SimuPay (corps JSON brut + horodatage + signature).
     *
     * @param  array<string, mixed>  $payload
     */
    private function sendWebhook(array $payload, ?string $secret = 'whsec_test', ?int $timestamp = null): TestResponse
    {
        $body = json_encode($payload);
        $timestamp ??= now()->timestamp;

        return $this->call('POST', route('payments.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SIMUPAY_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_SIMUPAY_SIGNATURE' => hash_hmac('sha256', $timestamp.'.'.$body, $secret),
        ], $body);
    }

    public function test_signed_success_marks_the_due_paid_with_a_proof_code(): void
    {
        $payment = Payment::factory()->create(['provider_id' => 'sp_ok']);

        $this->sendWebhook(['id' => 'sp_ok', 'status' => 'succeeded'])->assertOk();

        $due = $payment->due->fresh();
        $this->assertSame(DueStatus::Payee, $due->status);
        $this->assertMatchesRegularExpression('/^CC-[A-HJ-NP-Z2-9]{6}$/', $due->receipt_code);
        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
    }

    public function test_forged_signature_is_rejected_with_401(): void
    {
        $payment = Payment::factory()->create(['provider_id' => 'sp_forged']);

        $this->sendWebhook(['id' => 'sp_forged', 'status' => 'succeeded'], secret: 'pas_le_bon_secret')
            ->assertUnauthorized();

        $this->assertFalse($payment->due->fresh()->isPaid());
    }

    public function test_old_message_is_rejected_as_a_replay(): void
    {
        $payment = Payment::factory()->create(['provider_id' => 'sp_old']);

        $this->sendWebhook(['id' => 'sp_old', 'status' => 'succeeded'], timestamp: now()->subHour()->timestamp)
            ->assertUnauthorized();

        $this->assertFalse($payment->due->fresh()->isPaid());
    }

    public function test_receiving_the_same_success_twice_keeps_the_same_proof_code(): void
    {
        $payment = Payment::factory()->create(['provider_id' => 'sp_twice']);

        $this->sendWebhook(['id' => 'sp_twice', 'status' => 'succeeded']);
        $firstCode = $payment->due->fresh()->receipt_code;
        $this->sendWebhook(['id' => 'sp_twice', 'status' => 'succeeded'])->assertOk();

        $this->assertSame($firstCode, $payment->due->fresh()->receipt_code);
    }

    public function test_failed_payment_keeps_the_due_unpaid(): void
    {
        $payment = Payment::factory()->create(['provider_id' => 'sp_ko']);

        $this->sendWebhook(['id' => 'sp_ko', 'status' => 'failed', 'failure_reason' => 'insufficient_funds'])->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::Failed, $payment->status);
        $this->assertSame('insufficient_funds', $payment->failure_reason);
        $this->assertFalse($payment->due->isPaid());
    }
}
