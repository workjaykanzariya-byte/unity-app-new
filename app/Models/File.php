<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        'id',
        'uploader_user_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'width',
        'height',
        'duration',
        's3_key',
        'size_bytes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected $appends = ['url'];

    protected static function booted(): void
    {
        static::creating(function (self $file): void {
            if (! $file->id) {
                $file->id = (string) Str::uuid();
            }

            if (! $file->disk) {
                $file->disk = config('filesystems.default');
            }
        });
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_user_id');
    }

    public function getUrlAttribute(): ?string
    {
        $disk = $this->disk ?? config('filesystems.default');
        $path = $this->path ?? $this->s3_key;

        if (! $path) {
            return null;
        }

        return Storage::disk($disk)->url($path);
    }
}
