<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\MembershipStatus;
use App\Enums\PostType;
use App\Models\Association;
use App\Models\AssociationPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssociationManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    private function associationLedBy(User $leader): Association
    {
        return Association::factory()->active()->for($leader, 'leader')->create();
    }

    public function test_only_the_leader_can_open_the_dashboard(): void
    {
        $association = Association::factory()->active()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('associations.manage.show', $association))
            ->assertForbidden();
    }

    public function test_leader_accepts_a_membership_request(): void
    {
        $leader = User::factory()->create();
        $association = $this->associationLedBy($leader);
        $citizen = User::factory()->create();
        $request = $association->memberships()->create(['user_id' => $citizen->id, 'status' => MembershipStatus::EnAttente]);

        $this->actingAs($leader)->post(route('associations.manage.members.accept', [$association, $request]));

        $this->assertTrue($association->members()->whereKey($citizen->id)->exists());
    }

    public function test_leader_cannot_handle_requests_of_another_association(): void
    {
        $leader = User::factory()->create();
        $association = $this->associationLedBy($leader);
        $otherRequest = Association::factory()->active()->create()->memberships()->create([
            'user_id' => User::factory()->create()->id, 'status' => MembershipStatus::EnAttente,
        ]);

        $this->actingAs($leader)
            ->post(route('associations.manage.members.accept', [$association, $otherRequest]))
            ->assertNotFound();
    }

    public function test_announcement_requires_a_date(): void
    {
        $leader = User::factory()->create();
        $association = $this->associationLedBy($leader);

        $this->actingAs($leader)
            ->post(route('associations.manage.posts.store', $association), ['type' => 'annonce', 'body' => 'Nettoyage samedi'])
            ->assertSessionHasErrors(['event_at' => 'Indiquez la date du rendez-vous.']);
    }

    public function test_leader_publishes_a_video_of_a_cleanup(): void
    {
        Storage::fake('public');
        $leader = User::factory()->create();
        $association = $this->associationLedBy($leader);

        $this->actingAs($leader)->post(route('associations.manage.posts.store', $association), [
            'type' => 'publication',
            'body' => '45 sacs ramassés derrière l\'école',
            'media' => UploadedFile::fake()->create('nettoyage.mp4', 800, 'video/mp4'),
        ])->assertSessionHas('success');

        $post = AssociationPost::sole();
        $this->assertSame(PostType::Publication, $post->type);
        $this->assertTrue($post->isVideo());
        Storage::disk('public')->assertExists($post->media_path);
    }
}
