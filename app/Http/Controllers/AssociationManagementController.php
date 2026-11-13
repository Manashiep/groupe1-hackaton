<?php

namespace App\Http\Controllers;

use App\Enums\AssociationStatus;
use App\Enums\MembershipStatus;
use App\Enums\PostType;
use App\Models\Association;
use App\Models\AssociationPost;
use App\Models\Membership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Tableau de bord du chef de groupe : adhésions, annonces et publications, infos du groupe.
 */
class AssociationManagementController extends Controller
{
    public function show(Request $request, Association $association): View
    {
        $this->ensureLeader($request, $association);

        return view('associations.manage', [
            'association' => $association,
            'requests' => $association->memberships()->with('user')->where('status', MembershipStatus::EnAttente)->latest()->get(),
            'members' => $association->memberships()->with('user')->where('status', MembershipStatus::Membre)->get(),
            'posts' => $association->posts()->get(),
            'colors' => AssociationController::COLORS,
        ]);
    }

    public function update(Request $request, Association $association): RedirectResponse
    {
        $this->ensureLeader($request, $association);
        $association->update($request->validate([
            'description' => ['required', 'string', 'min:20', 'max:1000'],
            'color' => ['required', Rule::in(AssociationController::COLORS)],
        ]));

        return back()->with('success', 'Informations mises à jour.');
    }

    /**
     * Annonce (rendez-vous) ou publication, avec photo ou vidéo.
     */
    public function storePost(Request $request, Association $association): RedirectResponse
    {
        $this->ensureLeader($request, $association);
        $data = $request->validate([
            'type' => ['required', Rule::enum(PostType::class)],
            'body' => ['required', 'string', 'max:2000'],
            'media' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime', 'max:51200'],
            'event_at' => [Rule::requiredIf($request->input('type') === PostType::Annonce->value), 'nullable', 'date', 'after_or_equal:today'],
        ], [
            'body.required' => 'Écrivez quelques mots.',
            'event_at.required' => 'Indiquez la date du rendez-vous.',
            'media.mimetypes' => 'Envoyez une photo (JPG, PNG) ou une vidéo (MP4).',
        ]);

        $media = $request->file('media');
        $association->posts()->create([
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'body' => $data['body'],
            'event_at' => $data['type'] === PostType::Annonce->value ? $data['event_at'] : null,
            'media_path' => $media?->store('associations', 'public'),
            'media_type' => $media ? (str_starts_with($media->getMimeType(), 'video/') ? 'video' : 'image') : null,
        ]);

        return back()->with('success', 'Publié ! Les habitants le voient dans le fil des associations.');
    }

    public function destroyPost(Request $request, Association $association, AssociationPost $post): RedirectResponse
    {
        $this->ensureLeader($request, $association);
        abort_unless($post->association_id === $association->id, 404);
        $post->delete();

        return back()->with('success', 'Publication supprimée.');
    }

    public function acceptMember(Request $request, Association $association, Membership $membership): RedirectResponse
    {
        $this->ensureOwnMembership($request, $association, $membership);
        $membership->update(['status' => MembershipStatus::Membre]);

        return back()->with('success', $membership->user->shortName().' fait maintenant partie du groupe.');
    }

    public function refuseMember(Request $request, Association $association, Membership $membership): RedirectResponse
    {
        $this->ensureOwnMembership($request, $association, $membership);
        abort_if($membership->user_id === $association->leader_id, 422);
        $membership->delete();

        return back()->with('success', 'Demande retirée.');
    }

    private function ensureLeader(Request $request, Association $association): void
    {
        abort_unless($association->isLedBy($request->user()) && $association->status === AssociationStatus::Active, 403);
    }

    private function ensureOwnMembership(Request $request, Association $association, Membership $membership): void
    {
        $this->ensureLeader($request, $association);
        abort_unless($membership->association_id === $association->id, 404);
    }
}
