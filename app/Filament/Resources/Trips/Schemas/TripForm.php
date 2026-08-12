<?php

namespace App\Filament\Resources\Trips\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TripForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('shopped_on')
                    ->required(),
                TextInput::make('discount')
                    ->required()
                    ->numeric()
                    ->prefix('£')
                    ->default(0.0),
            ]);
    }
}
