<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Support\PriceColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_confirmed', 'desc')
            ->columns([
                // No placeholder for products without a photo yet — an
                // empty cell rather than a generic "no image" graphic.
                ImageColumn::make('image_path')
                    ->label('')
                    ->disk('public')
                    ->square()
                    ->size(40)
                    ->alignCenter()
                    ->alt(fn ($record) => $record->product_name),
                TextColumn::make('product_name')
                    ->searchable()
                    ->weight(FontWeight::Medium),
                PriceColumn::make('price')
                    ->sortable(),
                TextColumn::make('last_confirmed')
                    ->label(__('Last confirmed'))
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->last_confirmed->lt(now()->subDays(90)) ? 'warning' : 'success'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
