<?php

namespace App\Filament\Resources\BudgetChanges\Pages;

use App\Filament\Resources\BudgetChanges\BudgetChangeResource;
use App\Models\BudgetChange;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBudgetChange extends CreateRecord
{
    protected static string $resource = BudgetChangeResource::class;

    /**
     * A change made earlier this same (still-current) month is a correction,
     * not a rewrite of history, so it updates that month's entry in place
     * rather than creating a second row for the same effective_from date.
     */
    protected function handleRecordCreation(array $data): Model
    {
        return BudgetChange::updateOrCreate(
            ['effective_from' => now()->startOfMonth()->toDateString()],
            ['amount' => $data['amount']],
        );
    }
}
