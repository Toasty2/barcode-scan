<?php

namespace App\Filament\Resources\BudgetChanges;

use App\Filament\Resources\BudgetChanges\Pages\CreateBudgetChange;
use App\Filament\Resources\BudgetChanges\Pages\ListBudgetChanges;
use App\Filament\Resources\BudgetChanges\Schemas\BudgetChangeForm;
use App\Filament\Resources\BudgetChanges\Tables\BudgetChangesTable;
use App\Models\BudgetChange;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BudgetChangeResource extends Resource
{
    protected static ?string $model = BudgetChange::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return BudgetChangeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BudgetChangesTable::configure($table);
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBudgetChanges::route('/'),
            'create' => CreateBudgetChange::route('/create'),
        ];
    }
}
