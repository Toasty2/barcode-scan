<?php

namespace App\Models;

use Brick\Money\Money;
use Carbon\CarbonInterface;
use Elegantly\Money\MoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'shopped_on',
        'shop_id',
        'discount',
    ];

    protected function casts(): array
    {
        return [
            'shopped_on' => 'date',
            'discount' => MoneyCast::class,
        ];
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * This trip's own total, net of its discount. Assumes `purchases` is
     * already loaded (or lazy-loads it) — unlike the static period sums
     * below, this operates on a single already-fetched trip rather than
     * running its own aggregate query.
     */
    public function netSpend(): Money
    {
        $grossMinorUnits = $this->purchases->sum(
            fn (Purchase $purchase) => $purchase->quantity * $purchase->unit_price->getMinorAmount()->toInt()
        );

        return Money::ofMinor(
            $grossMinorUnits - $this->discount->getMinorAmount()->toInt(),
            config('money.default_currency')
        );
    }

    /**
     * Total spend across trips in the given date range, net of each trip's
     * discount — the line-item total minus discounts, not just the raw sum.
     * Both sums happen as integer minor-unit arithmetic in SQL
     */
    public static function netSpendForPeriod(CarbonInterface $start, CarbonInterface $end): Money
    {
        $grossMinorUnits = (int) static::whereBetween('shopped_on', [$start, $end])
            ->join('purchases', 'purchases.trip_id', '=', 'trips.id')
            ->sum(DB::raw('purchases.quantity * purchases.unit_price'));

        $totalDiscountMinorUnits = (int) static::whereBetween('shopped_on', [$start, $end])->sum('discount');

        return Money::ofMinor($grossMinorUnits - $totalDiscountMinorUnits, config('money.default_currency'));
    }

    public static function netSpendForMonth(CarbonInterface $month): Money
    {
        return static::netSpendForPeriod($month->clone()->startOfMonth(), $month->clone()->endOfMonth());
    }

    public static function netSpendForYear(CarbonInterface $year): Money
    {
        return static::netSpendForPeriod($year->clone()->startOfYear(), $year->clone()->endOfYear());
    }

    /**
     * Every calendar year with at least one trip in it, from the earliest
     * trip's year up to the current year — the range a year-over-year
     * comparison should cover, even for years with no trips in between.
     */
    public static function yearsWithTrips(): Collection
    {
        $earliest = static::min('shopped_on');

        if ($earliest === null) {
            return collect([Carbon::now()->year]);
        }

        return collect(range(Carbon::parse($earliest)->year, Carbon::now()->year));
    }

    public static function countForYear(int $year): int
    {
        return static::whereYear('shopped_on', $year)->count();
    }

    public static function itemCountForYear(int $year): int
    {
        return (int) static::whereYear('shopped_on', $year)
            ->join('purchases', 'purchases.trip_id', '=', 'trips.id')
            ->sum('purchases.quantity');
    }

    /**
     * Net spend for the given year, grouped by shop (including an
     * "unassigned" bucket for trips with no shop set). Aggregated in PHP
     * over eager-loaded trips/purchases rather than a single grouped SQL
     * query, since joining purchases and trips in one query would multiply
     * each trip's discount once per purchase row — the same reason
     * netSpendForPeriod() sums gross and discount as two separate queries.
     *
     * @return Collection<int, array{shop: ?Shop, spend: Money}>
     */
    public static function netSpendByShopForYear(int $year): Collection
    {
        $currency = config('money.default_currency');

        return static::whereYear('shopped_on', $year)
            ->with(['shop', 'purchases'])
            ->get()
            ->groupBy('shop_id')
            ->map(function (Collection $trips) use ($currency) {
                $grossMinorUnits = $trips->sum(
                    fn (self $trip) => $trip->purchases->sum(fn (Purchase $purchase) => $purchase->quantity * $purchase->unit_price->getMinorAmount()->toInt())
                );
                $discountMinorUnits = $trips->sum(fn (self $trip) => $trip->discount->getMinorAmount()->toInt());

                return [
                    'shop' => $trips->first()->shop,
                    'spend' => Money::ofMinor($grossMinorUnits - $discountMinorUnits, $currency),
                ];
            })
            ->values();
    }
}
