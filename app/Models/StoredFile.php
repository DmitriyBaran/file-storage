<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $original_name
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property string $extension
 * @property int $size
 * @property Carbon $expires_at
 * @property Carbon $created_at
 */
class StoredFile extends Model
{
    protected $fillable = [
        'original_name',
        'disk',
        'path',
        'mime_type',
        'extension',
        'size',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at->lte(now());
    }

    public function humanSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $this->size;
        $i = 0;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, $i === 0 ? 0 : 1).' '.$units[$i];
    }

    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'extension' => $this->extension,
            'size' => $this->size,
            'size_human' => $this->humanSize(),
            'created_at' => $this->created_at?->toIso8601String(),
            'expires_at' => $this->expires_at->toIso8601String(),
        ];
    }
}
