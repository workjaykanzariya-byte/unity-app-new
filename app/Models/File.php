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

    protected $fillable = [
        'id',
        'uploader_user_id',
        'disk',
        'path',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'duration',
        'original_name',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
