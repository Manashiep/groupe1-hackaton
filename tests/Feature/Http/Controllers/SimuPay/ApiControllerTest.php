<?php

namespace Tests\Feature\Http\Controllers\SimuPay;

use App\Models\SimuPayTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.simupay.secret_key' => 'sk_test_secret']);
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentPayload(): array
    {
        return [
            'amount' => 300,
            'currency' => 'KMF',
            'reference' => 'due_42',
            'description' => 'Redevance de collecte',
            'callback_url' => 'https://cleancomores.test/paiements/webhook',
            'return_url' => 'https://cleancomores.test/moi',
        ];
    }

    public function test_request_without_the_secret_key_is_refused_with_401(): void
    {
        $this->postJson(route('simupay.api.store'), $this->paymentPayload())
            ->assertUnauthorized();

        $this->assertDatabaseCount('simu_pay_transactions', 0);
    }

    public function test_creating_a_payment_returns_201_with_a_checkout_url(): void
    {
        $response = $this->withToken('sk_test_secret')
            ->postJson(route('simupay.api.store'), $this->paymentPayload())
            ->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('amount', 300)
            ->assertJsonPath('reference', 'due_42');

        $transaction = SimuPayTransaction::sole();
        $this->assertStringStartsWith('sp_', $transaction->public_id);
        $response->assertJsonPath('checkout_url', route('simupay.checkout.show', $transaction));
    }

    public function test_invalid_payment_is_refused_with_422(): void
    {
        $this->withToken('sk_test_secret')
            ->postJson(route('simupay.api.store'), [...$this->paymentPayload(), 'amount' => 0, 'currency' => 'EUR'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount', 'currency']);
    }

    public function test_reading_a_payment_returns_its_current_status(): void
    {
        $transaction = SimuPayTransaction::factory()->create(['status' => SimuPayTransaction::STATUS_SUCCEEDED]);

        $this->withToken('sk_test_secret')
            ->getJson(route('simupay.api.show', $transaction))
            ->assertOk()
            ->assertJsonPath('id', $transaction->public_id)
            ->assertJsonPath('status', 'succeeded');
    }
}
