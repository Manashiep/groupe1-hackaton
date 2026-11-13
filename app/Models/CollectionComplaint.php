<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionComplaint extends Model
{
    protected $fillable = ['collection_id', 'user_id', 'comment'];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
