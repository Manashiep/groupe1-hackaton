<?php

namespace Tests\Feature\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Association;
use App\Models\Due;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_mairie_deletes_a_user_without_history(): void
    {
        $citizen = User::factory()->create();
        $this->actingAs(User::factory()->responsable()->create());

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make('delete')->table($citizen));

        $this->assertModelMissing($citizen);
    }

    public function test_user_who_paid_dues_cannot_be_deleted(): void
    {
        $due = Due::factory()->create();
        $due->markPaid();
        $this->actingAs(User::factory()->responsable()->create());

        Livewire::test(ManageUsers::class)
            ->assertActionDisabled(TestAction::make('delete')->table($due->user));
    }

    public function test_association_leader_cannot_be_deleted(): void
    {
        $association = Association::factory()->create();
        $this->actingAs(User::factory()->responsable()->create());

        Livewire::test(ManageUsers::class)
            ->assertActionDisabled(TestAction::make('delete')->table($association->leader));
    }

    public function test_staff_cannot_delete_their_own_account(): void
    {
        $responsable = User::factory()->responsable()->create();
        $this->actingAs($responsable);

        Livewire::test(ManageUsers::class)
            ->assertActionDisabled(TestAction::make('delete')->table($responsable));
    }
}
