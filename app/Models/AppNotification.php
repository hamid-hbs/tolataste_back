<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $table = 'notifications';

    public $timestamps = false;

    protected $fillable = ['type', 'title', 'message', 'read'];

    protected function casts(): array
    {
        return ['read' => 'boolean'];
    }
}
