<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileModel extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'files';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = true;

    public const UPDATED_AT = null;

    protected $fillable = [
        'id',
        'uploader_user_id',
        's3_key',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'duration',
        'meta',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'meta' => 'array',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_user_id');
    }
}
