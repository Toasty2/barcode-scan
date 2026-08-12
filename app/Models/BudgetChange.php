<?php

namespace App\Models;

use App\Casts\PriceCast;
use App\Support\Money\Price;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

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
}
