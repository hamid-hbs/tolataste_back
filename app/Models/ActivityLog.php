<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'details',
    ];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(?User $user, string $action, ?array $details = null): void
    {
        if (! $user) {
            return;
        }

        self::create([
            'user_id' => $user->id,
            'action' => $action,
            'details' => $details,
        ]);
    }
}