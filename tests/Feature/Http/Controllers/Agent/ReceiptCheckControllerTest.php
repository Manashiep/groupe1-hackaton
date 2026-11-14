<?php

namespace Tests\Feature\Http\Controllers\Agent;

use App\Models\Due;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptCheckControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_code_shows_who_paid(): void
    {
        $due = Due::factory()->for(User::factory()->create(['name' => 'Nadia Abdallah']))->create();
        $due->markPaid();

        $this->actingAs(User::factory()->agent()->create())
            ->get(route('agent.receipts.check', ['code' => strtolower($due->fresh()->receipt_code)]))
            ->assertOk()
            ->assertSee('Paiement valide')
            ->assertSee('Nadia Abdallah');
    }

    public function test_unknown_code_is_reported(): void
    {
        $this->actingAs(User::factory()->agent()->create())
            ->get(route('agent.receipts.check', ['code' => 'CC-FAUX99']))
            ->assertSee('Code inconnu');
    }

    public function test_citizens_cannot_use_the_checker(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('agent.receipts.check'))
            ->assertForbidden();
    }
}
