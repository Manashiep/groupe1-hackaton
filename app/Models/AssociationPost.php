<?php

namespace App\Models;

use App\Enums\PostType;
use Database\Factories\AssociationPostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Annonce (rendez-vous de nettoyage) ou publication (photos, vidéos d'un nettoyage).
 */
class AssociationPost extends Model
{
    /** @use HasFactory<AssociationPostFactory> */
    use HasFactory;

    protected $fillable = ['association_id', 'user_id', 'type', 'body', 'media_path', 'media_type', 'event_at'];

    protected function casts(): array
    {
        return [
            'type' => PostType::class,
            'event_at' => 'datetime',
        ];
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function mediaUrl(): ?string
    {
        return $this->media_path ? Storage::disk('public')->url($this->media_path) : null;
    }

    public function isVideo(): bool
    {
        return $this->media_type === 'video';
    }
}
