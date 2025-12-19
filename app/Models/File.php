<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'files';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = true;

    public const UPDATED_AT = null;

    protected $fillable = [
        'uploader_user_id',
        's3_key',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'duration',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
