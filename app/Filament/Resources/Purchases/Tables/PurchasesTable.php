<?php

namespace App\Filament\Resources\Purchases\Tables;

use App\Filament\Support\PriceColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('trip.shopped_on', 'desc')
            ->columns([
                TextColumn::make('trip.shopped_on')
                    ->label(__('Trip date'))
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('product_name')
                    ->searchable(),
                TextColumn::make('entry_type')
                    ->label(__('Type'))
                    ->badge(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                PriceColumn::make('unit_price')
                    ->sortable(),
                PriceColumn::make('line_total')
                    ->label(__('Line total'))
                    ->state(fn ($record) => $record->unit_price->multiply($record->quantity)),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
