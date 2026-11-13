<?php

namespace Tests\Feature\Filament\Resources\Associations;

use App\Enums\AssociationStatus;
use App\Filament\Resources\Associations\Pages\ManageAssociations;
use App\Models\Association;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssociationResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_responsable_approves_an_association_request(): void
    {
        $association = Association::factory()->create();
        $this->actingAs(User::factory()->responsable()->create());

        Livewire::test(ManageAssociations::class)
            ->callAction(TestAction::make('approve')->table($association));

        $this->assertSame(AssociationStatus::Active, $association->fresh()->status);
    }

    public function test_refusing_a_request_requires_a_reason(): void
    {
        $association = Association::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageAssociations::class)
            ->callAction(TestAction::make('refuse')->table($association), data: ['admin_note' => ''])
            ->assertHasFormErrors(['admin_note' => 'required']);

        $this->assertSame(AssociationStatus::EnAttente, $association->fresh()->status);
    }
}
