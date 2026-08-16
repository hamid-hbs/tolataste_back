<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'table_id', 'type', 'note', 'server_id', 'client_id', 'status', 'total', 'paid',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_WAITING = 'waiting';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_READY = 'ready';

    public const STATUS_SERVED = 'served';

    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_DINE_IN = 'dine_in';

    public const TYPE_TAKEAWAY = 'takeaway';

    /**
     * Transitions autorisées depuis chaque état.
     */
    public const TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_WAITING, self::STATUS_CANCELLED],
        self::STATUS_WAITING => [self::STATUS_PREPARING, self::STATUS_CANCELLED],
        self::STATUS_PREPARING => [self::STATUS_READY, self::STATUS_CANCELLED],
        self::STATUS_READY => [self::STATUS_SERVED],
        self::STATUS_SERVED => [],
        self::STATUS_CANCELLED => [],
    ];

    protected function casts(): array
    {
        return ['paid' => 'boolean', 'total' => 'integer'];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(User::class, 'server_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Recalcule le total à partir des lignes.
     */
    public function recomputeTotal(): void
    {
        $this->total = $this->items->sum('total');
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function isActive(): bool
    {
        return ! in_array($this->status, [self::STATUS_SERVED, self::STATUS_CANCELLED]);
    }
}
