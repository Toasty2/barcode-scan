<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Str;

class ProductOverview extends StatsOverviewWidget
{
    // Shown only on a product's View page, not auto-attached to the
    // Dashboard.
    protected static bool $isDiscovered = false;

    public ?Product $record = null;

    protected function getStats(): array
    {
        if (! $this->record) {
            return [];
        }

        return [
            Stat::make(__('UPC'), Str::limit($this->record->upc, 16))
                ->extraAttributes(['title' => e($this->record->upc)]),
            Stat::make(__('Product name'), $this->record->product_name),
            Stat::make(__('Price'), $this->record->price->format()),
            Stat::make(__('Last confirmed'), $this->record->last_confirmed->format('d/m/Y'))
                ->color($this->record->last_confirmed->lt(now()->subDays(90)) ? 'warning' : 'success'),
        ];
    }
}
