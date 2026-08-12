<?php

namespace App\Casts;

use App\Support\Money\Currency;
use App\Support\Money\Price;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<Price, Price|int>
 */
class PriceCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Price
    {
        if ($value === null) {
            return null;
        }

        return new Price((int) $value, $this->currency());
    }

    /**
     * Deliberately strict: only a Price instance or a raw int of minor
     * units is accepted. A bare float/string is refused rather than
     * silently guessed at, since whether it means major or minor units is
     * exactly the ambiguity this whole design exists to remove.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Price) {
            return $value->minorUnits;
        }

        if (is_int($value)) {
            return $value;
        }

        throw new InvalidArgumentException(
            'Cannot assign a '.get_debug_type($value)." to a Price-cast attribute ({$key}). ".
            'Pass a Price instance, or a raw int of minor units.'
        );
    }

    private function currency(): Currency
    {
        $currencyClass = config('money.default_currency');

        return new $currencyClass();
    }
}
