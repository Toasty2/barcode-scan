<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasResponsiveStatsColumns;
use App\Models\Product;
use App\Models\Purchase;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductOverview extends StatsOverviewWidget
{
    use HasResponsiveStatsColumns;

    // Shown only on a product's View page, not auto-attached to the
    // Dashboard.
    protected static bool $isDiscovered = false;

    public ?Product $record = null;

    protected function getStats(): array
    {
        if (! $this->record) {
            return [];
        }

        $locale = config('money.default_locale');

        return [
            Stat::make(__('Price'), $this->record->price->formatToLocale($locale)),
            Stat::make(__('Last confirmed price'), $this->record->last_confirmed->format('d/m/Y'))
                ->color($this->record->last_confirmed->lt(now()->subDays(90)) ? 'warning' : 'success'),
            $this->totalPurchasesStat(),
            $this->priceDeltaStat(),
        ];
    }

    private function priceDeltaStat(): Stat
    {
        $locale = config('money.default_locale');
        $history = Purchase::priceHistoryForProduct($this->record);

        if ($history->isEmpty()) {
            return Stat::make(__('Price delta'), __('No purchase history yet'));
        }

        $firstPurchase = $history->first();
        $delta = $history->last()['price']->minus($firstPurchase['price']);
        $firstDate = $firstPurchase['date']->format('d/m/Y');

        return Stat::make(__('Price delta'), $delta->formatToLocale($locale))
            ->description(match (true) {
                $delta->isZero() => __('Same as first purchase on :date', ['date' => $firstDate]),
                $delta->isNegative() => __('Cheaper than first purchase on :date', ['date' => $firstDate]),
                default => __('More expensive than first purchase on :date', ['date' => $firstDate]),
            })
            ->color(match (true) {
                $delta->isZero() => 'gray',
                $delta->isNegative() => 'success',
                default => 'danger',
            });
    }

    private function totalPurchasesStat(): Stat
    {
        $totalQuantity = (int) Purchase::where('product_id', $this->record->id)->sum('quantity');

        return Stat::make(__('Total purchases'), (string) $totalQuantity);
    }
}
