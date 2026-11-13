{{-- Carte d'une publication : $post (avec association chargée), $showAssociation (bool), $deleteRoute (optionnel) --}}
@use('App\Enums\PostType')
<article class="post">
    <div class="post-head">
        <span class="asso-avatar" style="background: {{ $post->association->color }}">{{ $post->association->initials() }}</span>
        <div class="flex-grow-1" style="min-width:0">
            <a href="{{ route('associations.show', $post->association) }}">{{ $post->association->name }}</a>
            <div class="row-sub">{{ $post->created_at->diffForHumans() }}</div>
        </div>
        @if ($post->type === PostType::Annonce)
            <span class="pill pill-accent"><i class="bi bi-megaphone-fill"></i> Annonce</span>
        @endif
        @isset($deleteRoute)
            <form method="POST" action="{{ $deleteRoute }}" onsubmit="return confirm('Supprimer cette publication ?')">
                @csrf @method('DELETE')
                <button class="btn btn-ghost btn-sm" aria-label="Supprimer"><i class="bi bi-trash3"></i></button>
            </form>
        @endisset
    </div>
    <div class="post-body">{{ $post->body }}</div>
    @if ($post->type === PostType::Annonce && $post->event_at)
        <div class="event">
            <div class="date"><span>{{ $post->event_at->translatedFormat('M') }}</span><b>{{ $post->event_at->format('d') }}</b></div>
            <div><strong>{{ ucfirst($post->event_at->translatedFormat('l j F')) }}</strong><div>à {{ $post->event_at->format('H\hi') }}</div></div>
        </div>
    @endif
    @if ($post->mediaUrl())
        <div class="post-media">
            @if ($post->isVideo())
                <video src="{{ $post->mediaUrl() }}" controls preload="metadata" playsinline></video>
            @else
                <img src="{{ $post->mediaUrl() }}" alt="Photo publiée par {{ $post->association->name }}" loading="lazy">
            @endif
        </div>
    @endif
</article>
