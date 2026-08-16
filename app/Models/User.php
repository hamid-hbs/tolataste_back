<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_CLIENT = 'client';

    public const ROLE_SERVEUR = 'serveur';

    public const ROLE_CUISINE = 'cuisine';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_LEVEL = [
        self::ROLE_CLIENT => 0,
        self::ROLE_SERVEUR => 1,
        self::ROLE_CUISINE => 1,
        self::ROLE_MANAGER => 2,
        self::ROLE_ADMIN => 3,
    ];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Commandes prises par le serveur.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'server_id');
    }

    public function isStaff(): bool
    {
        return $this->role !== self::ROLE_CLIENT;
    }

    public function canAccess(string $requiredRole): bool
    {
        return (self::ROLE_LEVEL[$this->role] ?? 0) >= (self::ROLE_LEVEL[$requiredRole] ?? PHP_INT_MAX);
    }
}
