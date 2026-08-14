<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasResponsiveStatsColumns;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Shop;
use App\Models\Trip;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InsightsOverview extends StatsOverviewWidget
{
    use HasResponsiveStatsColumns;

    // Shown only on the Insights page, not auto-attached to the Dashboard.
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        return [
            $this->favouriteShopStat(),
            $this->busiestDayStat(),
            $this->cadenceStat(),
            $this->mostBoughtProductStat(),
            $this->biggestTripStat(),
        ];
    }

    private function favouriteShopStat(): Stat
    {
        $shopId = Trip::whereNotNull('shop_id')->pluck('shop_id')->countBy()->sortDesc()->keys()->first();

        if ($shopId === null) {
            return Stat::make(__('Favourite shop'), __('Not enough data yet'));
        }

        $shop = Shop::find($shopId);
        $tripCount = Trip::where('shop_id', $shopId)->count();

        return Stat::make(__('Favourite shop'), $shop->name)
            ->description(trans_choice(':count trip so far|:count trips so far', $tripCount, ['count' => $tripCount]));
    }

    private function busiestDayStat(): Stat
    {
        $dayCounts = Trip::all(['shopped_on'])->pluck('shopped_on')->map(fn ($date) => $date->format('l'))->countBy();

        if ($dayCounts->isEmpty()) {
            return Stat::make(__('Busiest shopping day'), __('Not enough data yet'));
        }

        $day = $dayCounts->sortDesc()->keys()->first();
        $count = $dayCounts[$day];

        return Stat::make(__('Busiest shopping day'), $day)
            ->description(trans_choice(':count trip on this day|:count trips on this day', $count, ['count' => $count]));
    }

    private function cadenceStat(): Stat
    {
        $dates = Trip::all(['shopped_on'])->pluck('shopped_on')->sort()->values();

        if ($dates->count() < 2) {
            return Stat::make(__('Shopping cadence'), __('Not enough data yet'));
        }

        $averageDays = (int) round($dates->first()->diffInDays($dates->last()) / ($dates->count() - 1));

        return Stat::make(__('Shopping cadence'), trans_choice('Every :count day|Every :count days', $averageDays, ['count' => $averageDays]));
    }

    private function mostBoughtProductStat(): Stat
    {
        $topProductId = Purchase::whereNotNull('product_id')
            ->selectRaw('product_id, SUM(quantity) as total_quantity')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit(1)
            ->value('product_id');

        if ($topProductId === null) {
            return Stat::make(__('Most-bought product'), __('Not enough data yet'));
        }

        $totalQuantity = (int) Purchase::where('product_id', $topProductId)->sum('quantity');
        $productName = Product::find($topProductId)?->product_name;

        return Stat::make(__('Most-bought product'), $productName)
            ->description(trans_choice('Bought :count time|Bought :count times', $totalQuantity, ['count' => $totalQuantity]));
    }

    private function biggestTripStat(): Stat
    {
        $trips = Trip::with('purchases')->get();

        if ($trips->isEmpty()) {
            return Stat::make(__('Biggest trip'), __('Not enough data yet'));
        }

        $biggest = $trips->sortByDesc(fn (Trip $trip) => $trip->netSpend()->minorUnits)->first();

        return Stat::make(__('Biggest trip'), $biggest->netSpend()->format())
            ->description($biggest->shopped_on->format('d/m/Y'));
    }
}
