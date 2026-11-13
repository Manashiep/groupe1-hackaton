<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Adhésion d'un citoyen à une association (demande, membre ou refus).
 */
class Membership extends Model
{
    protected $table = 'association_user';

    protected $fillable = ['association_id', 'user_id', 'status'];

    protected function casts(): array
    {
        return ['status' => MembershipStatus::class];
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
