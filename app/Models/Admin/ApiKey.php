<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A key that lets Claude (or any client) use api.openrevise.com. The key is
 * shown once when it is made; only its SHA-256 hash is stored.
 */
class ApiKey extends AdminModel
{
    public const ABILITIES = [
        'read' => 'Read: list the content tree, boards and lessons',
        'write' => 'Write: create and edit content (saved as drafts)',
        'publish' => 'Publish: content it sends can go live straight away',
    ];

    protected $casts = [
        'abilities' => 'array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function requests(): HasMany
    {
        return $this->hasMany(ApiRequest::class);
    }

    /**
     * @param  list<string>  $abilities
     * @return array{0: ApiKey, 1: string} The key row and the plain key.
     */
    public static function generate(string $name, array $abilities, ?\DateTimeInterface $expiresAt = null): array
    {
        $plain = 'or_'.Str::random(40);

        $key = static::create([
            'name' => $name,
            'prefix' => substr($plain, 0, 10),
            'key_hash' => hash('sha256', $plain),
            'abilities' => array_values($abilities),
            'expires_at' => $expiresAt,
        ]);

        return [$key, $plain];
    }

    public static function findByPlainKey(string $plain): ?self
    {
        return static::query()->where('key_hash', hash('sha256', $plain))->first();
    }

    public function scopeUsable(Builder $query): void
    {
        $query->whereNull('revoked_at')
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isUsable(): bool
    {
        return ! $this->revoked_at && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function can(string $ability): bool
    {
        $abilities = $this->abilities ?? [];

        // Publishing and writing both imply reading.
        return in_array($ability, $abilities, true)
            || ($ability === 'read' && $abilities !== [])
            || ($ability === 'write' && in_array('publish', $abilities, true));
    }

    public function status(): string
    {
        return match (true) {
            (bool) $this->revoked_at => 'Revoked',
            $this->expires_at && $this->expires_at->isPast() => 'Expired',
            default => 'Active',
        };
    }
}
