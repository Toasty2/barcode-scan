<?php

namespace App\Support\Money;

interface Currency
{
    public function code(): string;

    public function symbol(): string;

    public function decimalPlaces(): int;
}
