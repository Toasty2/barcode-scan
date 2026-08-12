<?php

namespace App\Models;

use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'colour',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public static function default(): ?self
    {
        return static::where('is_default', true)->first();
    }

    /**
     * The shade palette for this shop's badge, derived from its single
     * stored `colour` (a hex string picked via a colour swatch — nothing
     * else is stored). Filament generates the full light-background/
     * dark-text tint scale from that one hue, the same mechanism behind its
     * built-in named colours like 'success' or 'warning'. Null when no
     * colour has been set, so the badge falls back to Filament's default.
     */
    public function badgeColor(): ?array
    {
        return $this->colour ? Color::hex($this->colour) : null;
    }

    /**
     * Enforce at most one default shop, regardless of entry point (Filament,
     * tinker, factories) — saving a shop with is_default true unsets it on
     * every other row. $shop->id is null for a not-yet-saved record, so the
     * exclusion clause only applies once the record already exists.
     */
    protected static function booted(): void
    {
        static::saving(function (self $shop) {
            if (! $shop->is_default) {
                return;
            }

            $query = static::where('is_default', true);

            if ($shop->exists) {
                $query->where('id', '!=', $shop->id);
            }

            $query->update(['is_default' => false]);
        });
    }
}
