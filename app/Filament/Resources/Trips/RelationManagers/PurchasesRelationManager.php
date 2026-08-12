<?php

namespace App\Filament\Resources\Trips\RelationManagers;

use App\Filament\Support\PriceColumn;
use App\Filament\Support\PriceInput;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchasesRelationManager extends RelationManager
{
    protected static string $relationship = 'purchases';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('upc')
                    ->label('UPC')
                    ->maxLength(32),
                TextInput::make('product_name')
                    ->required()
                    ->maxLength(255),
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                TextColumn::make('upc')
                    ->label('UPC'),
                TextColumn::make('product_name'),
                TextColumn::make('entry_type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('quantity')
                    ->numeric(),
                PriceColumn::make('unit_price'),
                PriceColumn::make('line_total')
                    ->label('Line total')
                    ->state(fn ($record) => $record->unit_price->multiply($record->quantity)),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
