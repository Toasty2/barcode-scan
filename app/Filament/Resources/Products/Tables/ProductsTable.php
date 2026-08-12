<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Support\PriceColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('upc')
                    ->label('UPC')
                    ->searchable()
                    ->weight(FontWeight::Medium),
                TextColumn::make('product_name')
                    ->searchable(),
                PriceColumn::make('price')
                    ->sortable(),
                TextColumn::make('last_confirmed')
                    ->label('Last confirmed')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->last_confirmed->lt(now()->subDays(90)) ? 'warning' : 'success'),
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
