<?php

namespace App\Models;

use App\Casts\PriceCast;
use App\Support\Money\Price;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BudgetChange extends Model
{
    protected $fillable = [
        'amount',
        'effective_from',
    ];

    protected function casts(): array
    {
        return [
            'amount' => PriceCast::class,
            'effective_from' => 'date',
        ];
    }

    /**
     * The budget amount in effect on the given date — the most recent
     * change whose effective_from is on or before it.
     */
    public static function amountAsOf(CarbonInterface $date): ?Price
    {
        $change = static::where('effective_from', '<=', $date)
            ->orderByDesc('effective_from')
            ->first();

        return $change?->amount;
    }

    /**
     * The budget amount in effect for the given calendar month.
     */
    public static function amountForMonth(CarbonInterface $month): ?Price
    {
        return static::amountAsOf($month->clone()->startOfMonth());
    }

    /**
     * Every calendar month from the earliest budget's effective month
     * through the current month, inclusive — the full range a
     * budget-adherence history should cover. Empty if no budget has ever
     * been set.
     *
     * @return Collection<int, Carbon>
     */
    public static function monthsWithBudget(): Collection
    {
        $earliest = static::min('effective_from');

        if ($earliest === null) {
            return collect();
        }

        $month = Carbon::parse($earliest)->startOfMonth();
        $end = Carbon::now()->startOfMonth();
        $months = collect();

        while ($month->lessThanOrEqualTo($end)) {
            $months->push($month->clone());
            $month->addMonth();
        }

        return $months;
    }
}
