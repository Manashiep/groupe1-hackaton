<?php

namespace Tests\Feature\Filament\Resources\Dues;

use App\Enums\DueStatus;
use App\Filament\Resources\Dues\Pages\ManageDues;
use App\Models\Due;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DueResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_mairie_filters_unpaid_dues(): void
    {
        $unpaid = Due::factory()->create();
        $paid = Due::factory()->create();
        $paid->markPaid();
        $this->actingAs(User::factory()->responsable()->create());

        Livewire::test(ManageDues::class)
            ->filterTable('status', DueStatus::APayer->value)
            ->assertCanSeeTableRecords([$unpaid])
            ->assertCanNotSeeTableRecords([$paid]);
    }
}
