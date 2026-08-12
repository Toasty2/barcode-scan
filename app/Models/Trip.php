<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'shopped_on',
        'discount',
    ];

    protected function casts(): array
    {
        return [
            'shopped_on' => 'date',
            'discount' => 'decimal:2',
        ];
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Total spend across trips in the given date range, net of each trip's
     * discount — the line-item total minus discounts, not just the raw sum.
     */
    public static function netSpendForPeriod(CarbonInterface $start, CarbonInterface $end): float
    {
        $grossSpend = (float) static::whereBetween('shopped_on', [$start, $end])
            ->join('purchases', 'purchases.trip_id', '=', 'trips.id')
            ->sum(DB::raw('purchases.quantity * purchases.unit_price'));

        $totalDiscount = (float) static::whereBetween('shopped_on', [$start, $end])->sum('discount');

        return $grossSpend - $totalDiscount;
    }

    public static function netSpendForMonth(CarbonInterface $month): float
    {
        return static::netSpendForPeriod($month->clone()->startOfMonth(), $month->clone()->endOfMonth());
    }

    public static function netSpendForYear(CarbonInterface $year): float
    {
        return static::netSpendForPeriod($year->clone()->startOfYear(), $year->clone()->endOfYear());
    }
}
