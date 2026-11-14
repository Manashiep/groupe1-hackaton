<?php

namespace Tests\Feature\Http\Controllers\SimuPay;

use App\Models\SimuPayTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.simupay.webhook_secret' => 'whsec_test']);
        Http::fake(['*' => Http::response(['received' => true], 200)]);
    }

    public function test_successful_payment_sends_a_signed_webhook_and_returns_to_the_merchant(): void
    {
        $transaction = SimuPayTransaction::factory()->create();

        $this->post(route('simupay.checkout.confirm', $transaction), ['phone' => '+269 321 45 67', 'pin' => '1234', 'outcome' => 'success'])
            ->assertRedirect($transaction->return_url);

        $transaction->refresh();
        $this->assertSame(SimuPayTransaction::STATUS_SUCCEEDED, $transaction->status);
        $this->assertSame(200, $transaction->webhook_status);

        Http::assertSent(function (Request $request) use ($transaction): bool {
            $expected = hash_hmac('sha256', $request->header('X-SimuPay-Timestamp')[0].'.'.$request->body(), 'whsec_test');

            return $request->url() === $transaction->callback_url
                && $request['status'] === 'succeeded'
                && $request['reference'] === $transaction->merchant_reference
                && hash_equals($expected, $request->header('X-SimuPay-Signature')[0]);
        });
    }

    public function test_insufficient_funds_marks_the_payment_failed(): void
    {
        $transaction = SimuPayTransaction::factory()->create();

        $this->post(route('simupay.checkout.confirm', $transaction), ['phone' => '+269 321 45 67', 'pin' => '1234', 'outcome' => 'insufficient_funds']);

        $transaction->refresh();
        $this->assertSame(SimuPayTransaction::STATUS_FAILED, $transaction->status);
        $this->assertSame('insufficient_funds', $transaction->failure_reason);
        Http::assertSent(fn (Request $request): bool => $request['status'] === 'failed');
    }

    public function test_pin_must_have_four_digits(): void
    {
        $transaction = SimuPayTransaction::factory()->create();

        $this->post(route('simupay.checkout.confirm', $transaction), ['phone' => '+269 321 45 67', 'pin' => '12', 'outcome' => 'success'])
            ->assertSessionHasErrors(['pin' => 'Le code secret fait 4 chiffres.']);

        $this->assertTrue($transaction->fresh()->isPending());
        Http::assertNothingSent();
    }

    public function test_an_already_paid_transaction_cannot_be_paid_again(): void
    {
        $transaction = SimuPayTransaction::factory()->create(['status' => SimuPayTransaction::STATUS_SUCCEEDED]);

        $this->post(route('simupay.checkout.confirm', $transaction), ['phone' => '+269 321 45 67', 'pin' => '1234', 'outcome' => 'success'])
            ->assertRedirect($transaction->return_url);

        Http::assertNothingSent();
    }
}
