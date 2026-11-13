<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Point de collecte validé par l'agent pour une journée donnée.
 */
class RouteStopVisit extends Model
{
    protected $fillable = ['route_stop_id', 'user_id', 'visit_date'];

    protected function casts(): array
    {
        return ['visit_date' => 'date'];
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(RouteStop::class, 'route_stop_id');
    }
}
