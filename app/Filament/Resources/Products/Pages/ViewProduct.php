<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Widgets\ProductPriceHistoryChart;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getFooterWidgets(): array
    {
        return [
            ProductPriceHistoryChart::class,
        ];
    }
}
