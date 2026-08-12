<?php

namespace App\Support\Money\Currencies;

use App\Support\Money\Currency;

class GBP implements Currency
{
    public function code(): string
    {
        return 'GBP';
    }

    public function symbol(): string
    {
        return '£';
    }

    public function decimalPlaces(): int
    {
        return 2;
    }
}
