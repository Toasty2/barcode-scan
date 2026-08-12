<?php

namespace Tests\Unit\Support\Money;

use App\Support\Money\Currencies\GBP;
use App\Support\Money\Currency;
use App\Support\Money\Price;
use InvalidArgumentException;
use Tests\TestCase;

class PriceTest extends TestCase
{
    public function test_from_major_units_rounds_to_the_nearest_minor_unit(): void
    {
        $this->assertSame(199, Price::fromMajorUnits('1.99', new GBP())->minorUnits);
        $this->assertSame(150, Price::fromMajorUnits(1.5, new GBP())->minorUnits);
    }

    public function test_to_major_units_converts_back(): void
    {
        $this->assertSame(1.99, (new Price(199, new GBP()))->toMajorUnits());
    }

    public function test_add_and_subtract_operate_on_minor_units(): void
    {
        $a = new Price(199, new GBP());
        $b = new Price(50, new GBP());

        $this->assertSame(249, $a->add($b)->minorUnits);
        $this->assertSame(149, $a->subtract($b)->minorUnits);
    }

    public function test_multiply_scales_minor_units(): void
    {
        $this->assertSame(597, (new Price(199, new GBP()))->multiply(3)->minorUnits);
    }

    public function test_is_negative_and_abs(): void
    {
        $negative = new Price(-250, new GBP());

        $this->assertTrue($negative->isNegative());
        $this->assertSame(250, $negative->abs()->minorUnits);
        $this->assertFalse($negative->abs()->isNegative());
    }

    public function test_format_includes_symbol_and_sign(): void
    {
        $this->assertSame('£1.99', (new Price(199, new GBP()))->format());
        $this->assertSame('-£1.99', (new Price(-199, new GBP()))->format());
    }

    public function test_combining_different_currencies_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Price(100, new GBP()))->add(new Price(100, $this->otherCurrency()));
    }

    private function otherCurrency(): Currency
    {
        return new class implements Currency
        {
            public function code(): string
            {
                return 'USD';
            }

            public function symbol(): string
            {
                return '$';
            }

            public function decimalPlaces(): int
            {
                return 2;
            }
        };
    }
}
