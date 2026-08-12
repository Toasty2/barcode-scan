<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Support\PriceInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('upc')
                    ->label('UPC')
                    ->required()
                    ->maxLength(32)
                    ->disabledOn('edit'),
                TextInput::make('product_name')
                    ->required(),
                PriceInput::make('price')
                    ->required(),
                DateTimePicker::make('last_confirmed')
                    ->required(),
            ]);
    }
}
