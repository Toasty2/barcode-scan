<?php

namespace App\Filament\Resources\Trips\Schemas;

use App\Filament\Support\PriceInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class TripForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('shopped_on')
                    ->required(),
                Select::make('shop_id')
                    ->label('Shop')
                    ->relationship('shop', 'name')
                    ->searchable(),
                PriceInput::make('discount')
                    ->required()
                    ->default('0'),
            ]);
    }
}
