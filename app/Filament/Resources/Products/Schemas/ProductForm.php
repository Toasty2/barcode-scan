<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Support\PriceInput;
use App\Models\Product;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('upc')
                    ->label(__('UPC'))
                    ->maxLength(32)
                    ->unique(ignoreRecord: true),
                TextInput::make('product_name')
                    ->required(),
                PriceInput::make('price')
                    ->required(),
                DateTimePicker::make('last_confirmed')
                    ->required(),
                FileUpload::make('image_path')
                    ->label(__('Photo'))
                    ->image()
                    ->disk('public')
                    ->directory('products'),
                Select::make('replaces_product_id')
                    ->label(__('Replaces'))
                    ->relationship(
                        name: 'replacesProduct',
                        titleAttribute: 'product_name',
                        modifyQueryUsing: fn (Builder $query, ?Product $record) => $record
                            ? $query->whereKeyNot($record->getKey())
                            : $query,
                    )
                    ->searchable()
                    ->preload()
                    ->helperText(__('If this is a newer variant of an existing product (e.g. a shrunk successor pack), link it here to keep its price history continuous.')),
            ]);
    }
}
