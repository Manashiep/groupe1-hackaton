<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportEvent extends Model
{
    protected $fillable = ['report_id', 'user_id', 'status', 'note', 'created_at'];

    protected function casts(): array
    {
        return ['status' => ReportStatus::class];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
