<?php

namespace App\Filament\Resources\Purchases\Schemas;

use App\Filament\Support\PriceInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PurchaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('trip_id')
                    ->relationship('trip', 'shopped_on')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->shopped_on->format('d/m/Y')." (#{$record->id})")
                    ->searchable()
                    ->required(),
                TextInput::make('upc')
                    ->label('UPC')
                    ->maxLength(32),
                TextInput::make('product_name')
                    ->required(),
                Select::make('entry_type')
                    ->label('Type')
                    ->options(['scan' => 'Scan', 'lump_sum' => 'Lump sum'])
                    ->default('scan')
                    ->required(),
                TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(1),
                PriceInput::make('unit_price')
                    ->required(),
            ]);
    }
}
