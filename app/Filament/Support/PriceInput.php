<?php

namespace App\Filament\Support;

use App\Support\Money\Currency;
use App\Support\Money\Price;
use Filament\Forms\Components\TextInput;

/**
 * A TextInput bound to a Price-cast attribute: it displays the Price's
 * major-unit amount for editing and dehydrates the typed value back into a
 * Price, so form/table classes never have to touch that conversion or
 * import a currency directly.
 */
class PriceInput
{
    public static function make(string $name): TextInput
    {
        $currency = self::currency();

        return TextInput::make($name)
            // Deliberately not ->numeric(): that registers Filament's
            // NumberStateCast, which floatval()s the raw state — but our
            // raw state is a Price object, not a number. The 'numeric' rule
            // and decimal keyboard are set directly instead.
            ->inputMode('decimal')
            ->rule('numeric')
            ->prefix($currency->symbol())
            ->formatStateUsing(fn (mixed $state) => $state instanceof Price
                ? number_format($state->toMajorUnits(), $currency->decimalPlaces(), '.', '')
                : $state)
            ->dehydrateStateUsing(fn (mixed $state) => Price::fromMajorUnits($state, $currency));
    }

    private static function currency(): Currency
    {
        $currencyClass = config('money.default_currency');

        return new $currencyClass();
    }
}
