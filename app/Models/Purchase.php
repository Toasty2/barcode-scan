<?php

namespace App\Models;

use App\Casts\PriceCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'product_id',
        'product_name',
        'entry_type',
        'quantity',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => PriceCast::class,
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Every price actually paid for this product, oldest first — spanning
     * its full succession chain (this product plus whatever it replaced,
     * and whatever that replaced, and so on), not just this one barcode's
     * own short history. Built from purchases.unit_price snapshots (what
     * was really paid on each trip), not the products cache, which only
     * ever holds the current price.
     *
     * @return Collection<int, array{date: Carbon, price: \App\Support\Money\Price}>
     */
    public static function priceHistoryForProduct(Product $product): Collection
    {
        $productIds = [];
        $current = $product;

        while ($current !== null) {
            $productIds[] = $current->id;
            $current = $current->replacesProduct;
        }

        return static::query()
            ->join('trips', 'trips.id', '=', 'purchases.trip_id')
            ->whereIn('purchases.product_id', $productIds)
            ->orderBy('trips.shopped_on')
            ->get(['purchases.unit_price', 'trips.shopped_on'])
            ->values()
            ->map(fn (self $purchase) => [
                'date' => Carbon::parse($purchase->shopped_on),
                'price' => $purchase->unit_price,
            ]);
    }
}
