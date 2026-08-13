<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Widgets\ProductOverview;
use App\Filament\Widgets\ProductPriceHistoryChart;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    // Overridden rather than using the default form/infolist content: a
    // disabled copy of the edit form is pointless when nothing here is
    // editable, so the product's fields are shown as stat pills instead.
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(1)->schema($this->getWidgetsSchemaComponents([ProductOverview::class])),
            $this->getRelationManagersContentComponent(),
        ]);
    }

    protected function getFooterWidgets(): array
    {
        return [
            ProductPriceHistoryChart::class,
        ];
    }
}
