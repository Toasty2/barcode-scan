<?php

namespace App\Filament\Resources\BudgetChanges\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BudgetChangeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('amount')
                    ->label('New monthly budget')
                    ->required()
                    ->numeric()
                    ->prefix('£')
                    ->helperText('Applies from the start of this calendar month onwards. Past months keep whatever budget was in effect for them at the time.'),
            ]);
    }
}
