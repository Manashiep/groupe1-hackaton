<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\AssociationStatus;
use App\Enums\MembershipStatus;
use App\Models\Association;
use App\Models\AssociationPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssociationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_asks_the_town_hall_to_create_an_association(): void
    {
        $citizen = User::factory()->create();

        $this->actingAs($citizen)->post(route('associations.store'), [
            'name' => 'Les Jeunes de Mvouni',
            'description' => 'Un groupe de jeunes qui nettoie les abords du marché.',
            'motivation' => 'Nous sommes déjà douze volontaires.',
            'color' => '#a16207',
        ])->assertRedirect(route('profile'));

        $association = Association::sole();
        $this->assertSame(AssociationStatus::EnAttente, $association->status);
        $this->assertTrue($association->leader->is($citizen));
        $this->assertSame(MembershipStatus::Membre, $association->membershipOf($citizen)->status);
    }

    public function test_pending_association_is_hidden_from_other_citizens(): void
    {
        $association = Association::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('associations.show', $association))
            ->assertNotFound();
    }

    public function test_feed_shows_posts_of_active_associations_only(): void
    {
        AssociationPost::factory()->create(['body' => 'Nettoyage de la plage dimanche']);
        AssociationPost::factory()->for(Association::factory())->create(['body' => 'Groupe pas encore validé']);

        $this->actingAs(User::factory()->create())
            ->get(route('associations.index'))
            ->assertOk()
            ->assertSee('Nettoyage de la plage dimanche')
            ->assertDontSee('Groupe pas encore validé');
    }

    public function test_joining_sends_a_pending_request(): void
    {
        $association = Association::factory()->active()->create();
        $citizen = User::factory()->create();

        $this->actingAs($citizen)->post(route('associations.join', $association))->assertSessionHas('success');

        $this->assertSame(MembershipStatus::EnAttente, $association->membershipOf($citizen)->status);
        $this->assertFalse($association->members()->whereKey($citizen->id)->exists());
    }

    public function test_cannot_join_an_association_that_is_not_active(): void
    {
        $association = Association::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('associations.join', $association))
            ->assertNotFound();
    }
}
