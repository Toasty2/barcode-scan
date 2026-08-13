<?php

namespace App\Filament\Resources\BudgetChanges\Schemas;

use App\Filament\Support\PriceInput;
use Filament\Schemas\Schema;

class BudgetChangeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                PriceInput::make('amount')
                    ->label(__('New monthly budget'))
                    ->required()
                    ->helperText(__('Applies from the start of this calendar month onwards. Past months keep whatever budget was in effect for them at the time.')),
            ]);
    }
}
