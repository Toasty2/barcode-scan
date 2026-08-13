<?php

namespace App\Filament\Resources\Shops\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ShopForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                ColorPicker::make('colour')
                    ->helperText(__('Used for this shop\'s tag colour. A lighter background shade is derived from it automatically.')),
                Toggle::make('is_default')
                    ->label(__('Default'))
                    ->helperText(__('Pre-selected when logging a new trip. Only one shop can be the default.')),
            ]);
    }
}
