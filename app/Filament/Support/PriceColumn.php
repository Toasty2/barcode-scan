<?php

namespace App\Filament\Support;

use App\Support\Money\Price;
use Filament\Tables\Columns\TextColumn;

/**
 * A TextColumn that displays a Price-cast attribute (or any state resolving
 * to a Price, e.g. via ->state()) using the Price's own formatting.
 */
class PriceColumn
{
    public static function make(string $name): TextColumn
    {
        return TextColumn::make($name)
            ->formatStateUsing(fn (mixed $state) => $state instanceof Price ? $state->format() : $state);
    }
}
