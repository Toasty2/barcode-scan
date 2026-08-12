<?php

namespace App\Models;

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
            'amount' => 'decimal:2',
            'effective_from' => 'date',
        ];
    }

    /**
     * The budget amount in effect on the given date — the most recent
     * change whose effective_from is on or before it.
     */
    public static function amountAsOf(CarbonInterface $date): ?string
    {
        return static::where('effective_from', '<=', $date)
            ->orderByDesc('effective_from')
            ->value('amount');
    }

    /**
     * The budget amount in effect for the given calendar month.
     */
    public static function amountForMonth(CarbonInterface $month): ?string
    {
        return static::amountAsOf($month->clone()->startOfMonth());
    }
}
