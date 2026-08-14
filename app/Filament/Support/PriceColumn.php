<?php

namespace App\Filament\Support;

use Brick\Money\Money;
use Filament\Tables\Columns\TextColumn;

/**
 * A TextColumn that displays a Money-cast attribute (or any state resolving
 * to a Money instance, e.g. via ->state()) using locale-aware formatting.
 */
class PriceColumn
{
    public static function make(string $name): TextColumn
    {
        return TextColumn::make($name)
            ->formatStateUsing(fn (mixed $state) => $state instanceof Money
                ? $state->formatToLocale(config('money.default_locale'))
                : $state);
    }
}
