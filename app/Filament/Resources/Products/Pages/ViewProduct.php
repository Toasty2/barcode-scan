<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Widgets\ProductOverview;
use App\Filament\Widgets\ProductPriceHistoryChart;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    // The default "View Product" title is redundant next to the
    // "Products > View" breadcrumb above it — show the product's own name
    // instead, with its UPC as a smaller subheading underneath.
    public function getTitle(): string | Htmlable
    {
        return $this->getRecord()->product_name;
    }

    public function getSubheading(): string | Htmlable | null
    {
        return $this->getRecord()->upc;
    }

    // Overridden rather than using the default form/infolist content: a
    // disabled copy of the edit form is pointless when nothing here is
    // editable, so the product's fields are shown as stat pills instead,
    // with the decorative photo (if any) above them.
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedSchema::make('infolist'),
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
