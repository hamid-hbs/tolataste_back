<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    public $incrementing = false;   // id fixe = 1

    protected $keyType = 'string';

    protected $table = 'settings';

    public $timestamps = false;

    protected $fillable = ['id', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public static function config(): array
    {
        return self::firstOrCreate(['id' => 1], ['data' => []])->data;
    }

    public static function put(array $data): Settings
    {
        $settings = self::firstOrCreate(['id' => 1], ['data' => []]);
        $settings->update(['data' => array_merge($settings->data ?? [], $data)]);

        return $settings;
    }
}
