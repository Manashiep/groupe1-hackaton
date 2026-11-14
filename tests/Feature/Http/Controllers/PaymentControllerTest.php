<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\DueStatus;
use App\Enums\PaymentStatus;
use App\Models\Due;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.simupay.url' => 'https://simupay.test/simupay', 'services.simupay.secret_key' => 'sk_test_secret']);
    }

    public function test_paying_creates_the_payment_at_simupay_and_redirects_to_its_checkout_page(): void
    {
        Http::fake(['simupay.test/*' => Http::response(['id' => 'sp_abc123', 'status' => 'pending', 'checkout_url' => 'https://simupay.test/simupay/checkout/sp_abc123'], 201)]);
        $due = Due::factory()->create();

        $this->actingAs($due->user)
            ->post(route('dues.pay', $due))
            ->assertRedirect('https://simupay.test/simupay/checkout/sp_abc123');

        $payment = Payment::sole();
        $this->assertSame('sp_abc123', $payment->provider_id);
        $this->assertSame(PaymentStatus::Pending, $payment->status);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://simupay.test/simupay/api/payments'
            && $request->hasHeader('Authorization', 'Bearer sk_test_secret')
            && $request['amount'] === 300
            && $request['reference'] === $payment->reference()
            && $request['callback_url'] === route('payments.webhook'));
    }

    public function test_cannot_pay_someone_elses_due(): void
    {
        Http::fake();
        $due = Due::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('dues.pay', $due))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_unavailable_gateway_shows_an_error_and_marks_the_attempt_failed(): void
    {
        Http::fake(['simupay.test/*' => Http::response(['error' => 'down'], 500)]);
        $due = Due::factory()->create();

        $this->actingAs($due->user)
            ->post(route('dues.pay', $due))
            ->assertSessionHasErrors('payment');

        $this->assertSame('gateway_unavailable', Payment::sole()->failure_reason);
        $this->assertSame(DueStatus::APayer, $due->fresh()->status);
    }

    public function test_return_page_asks_simupay_when_the_webhook_has_not_arrived_yet(): void
    {
        Http::fake(['simupay.test/simupay/api/payments/sp_late' => Http::response(['id' => 'sp_late', 'status' => 'succeeded'])]);
        $payment = Payment::factory()->create(['provider_id' => 'sp_late']);

        $this->actingAs($payment->user)
            ->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee('Paiement réussi');

        $this->assertTrue($payment->due->fresh()->isPaid());
    }

    public function test_receipt_shows_the_proof_code_only_once_paid(): void
    {
        $due = Due::factory()->create();

        $this->actingAs($due->user)->get(route('dues.receipt', $due))->assertNotFound();

        $due->markPaid();

        $this->actingAs($due->user)
            ->get(route('dues.receipt', $due))
            ->assertOk()
            ->assertSee($due->fresh()->receipt_code);
    }
}
