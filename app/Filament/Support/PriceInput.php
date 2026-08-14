<?php

namespace App\Filament\Support;

use Brick\Math\RoundingMode;
use Brick\Money\Currency;
use Brick\Money\Money;
use Filament\Forms\Components\TextInput;
use NumberFormatter;

/**
 * A TextInput bound to a Money-cast attribute: it displays the Money's
 * major-unit amount for editing and dehydrates the typed value back into a
 * Money instance, so form/table classes never have to touch that conversion
 * or import a currency directly.
 */
class PriceInput
{
    public static function make(string $name): TextInput
    {
        $currency = Currency::of(config('money.default_currency'));

        return TextInput::make($name)
            // Deliberately not ->numeric(): that registers Filament's
            // NumberStateCast, which floatval()s the raw state — but our
            // raw state is a Money object, not a number. The 'numeric' rule
            // and decimal keyboard are set directly instead.
            ->inputMode('decimal')
            ->rule('numeric')
            ->prefix(self::currencySymbol($currency))
            ->formatStateUsing(fn (mixed $state) => $state instanceof Money
                ? number_format($state->getAmount()->toFloat(), $currency->getDefaultFractionDigits(), '.', '')
                : $state)
            ->dehydrateStateUsing(fn (mixed $state) => Money::of($state, $currency, roundingMode: RoundingMode::HalfUp));
    }

    private static function currencySymbol(Currency $currency): string
    {
        $formatter = new NumberFormatter(config('money.default_locale'), NumberFormatter::CURRENCY);
        $formatter->setTextAttribute(NumberFormatter::CURRENCY_CODE, $currency->getCurrencyCode());

        return $formatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
    }
}
