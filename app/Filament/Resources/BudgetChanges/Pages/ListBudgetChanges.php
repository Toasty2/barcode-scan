<?php

namespace App\Filament\Resources\BudgetChanges\Pages;

use App\Filament\Resources\BudgetChanges\BudgetChangeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBudgetChanges extends ListRecords
{
    protected static string $resource = BudgetChangeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
