<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class Role extends Model
{
    use HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'key',
        'name',
        'description',
    ];

    public static function idByKey(string $key): ?string
    {
        return static::query()
            ->where('key', $key)
            ->value('id');
    }

    public static function mustIdByKey(string $key): string
    {
        $roleId = static::idByKey($key);

        if (! $roleId) {
            Log::error('Role key missing in roles table.', [
                'role_key' => $key,
            ]);

            throw new RuntimeException("Role key '{$key}' not found in roles table.");
        }

        return $roleId;
    }
}
