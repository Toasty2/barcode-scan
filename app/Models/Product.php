<?php

namespace App\Models;

use App\Casts\PriceCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'upc',
        'product_name',
        'price',
        'last_confirmed',
        'image_path',
        'replaces_product_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => PriceCast::class,
            'last_confirmed' => 'datetime',
        ];
    }

    // Barcode-based URLs (e.g. /admin/products/{upc}) rather than the
    // opaque numeric id — every product reachable this way already has a
    // upc in practice, since a row only ever gets created via an actual
    // scan.
    public function getRouteKeyName(): string
    {
        return 'upc';
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    // The product this one directly replaced (e.g. a shrunk successor
    // pack) — a one-to-one succession link, not shared "family" membership.
    // A simultaneous different-size product is a distinct, unrelated
    // product, never linked here.
    public function replacesProduct(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_product_id');
    }

    // The product that replaced this one, if any.
    public function replacedBy(): HasOne
    {
        return $this->hasOne(self::class, 'replaces_product_id');
    }
}
