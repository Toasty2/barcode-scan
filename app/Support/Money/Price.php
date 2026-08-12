<?php

namespace App\Support\Money;

use InvalidArgumentException;

final class Price
{
    public function __construct(
        public readonly int $minorUnits,
        public readonly Currency $currency,
    ) {}

    /**
     * Build a Price from a human-entered value in major units (e.g. "3.29")
     * rounding rather than truncating so float representation
     * error can't silently shift the result by a minor unit.
     */
    public static function fromMajorUnits(float|string $amount, Currency $currency): self
    {
        $minorUnits = (int) round(((float) $amount) * (10 ** $currency->decimalPlaces()));

        return new self($minorUnits, $currency);
    }

    public function toMajorUnits(): float
    {
        return $this->minorUnits / (10 ** $this->currency->decimalPlaces());
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits + $other->minorUnits, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits - $other->minorUnits, $this->currency);
    }

    public function multiply(int $factor): self
    {
        return new self($this->minorUnits * $factor, $this->currency);
    }

    public function isNegative(): bool
    {
        return $this->minorUnits < 0;
    }

    public function abs(): self
    {
        return new self(abs($this->minorUnits), $this->currency);
    }

    public function format(): string
    {
        $sign = $this->isNegative() ? '-' : '';
        $formatted = number_format(abs($this->toMajorUnits()), $this->currency->decimalPlaces());

        return "{$sign}{$this->currency->symbol()}{$formatted}";
    }

    private function assertSameCurrency(self $other): void
    {
        if ($other->currency->code() !== $this->currency->code()) {
            throw new InvalidArgumentException(
                "Cannot combine {$this->currency->code()} with {$other->currency->code()}."
            );
        }
    }
}
