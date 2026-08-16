<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    protected $table = 'dining_tables';

    protected $fillable = ['number', 'zone', 'status'];

    public const STATUS_FREE = 'free';

    public const STATUS_OCCUPIED = 'occupied';

    protected function casts(): array
    {
        return ['status' => 'string'];
    }

    /**
     * Historique des commandes passées sur la table.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'table_id');
    }

    public function free(): void
    {
        $this->update(['status' => self::STATUS_FREE]);
    }

    public function occupy(): void
    {
        $this->update(['status' => self::STATUS_OCCUPIED]);
    }
}
