<?php

namespace Tests\Unit\Casts;

use App\Casts\PriceCast;
use App\Models\Product;
use App\Support\Money\Currencies\GBP;
use App\Support\Money\Price;
use InvalidArgumentException;
use Tests\TestCase;

class PriceCastTest extends TestCase
{
    public function test_get_wraps_raw_minor_units_in_a_price(): void
    {
        $price = (new PriceCast())->get(new Product(), 'price', 199, []);

        $this->assertInstanceOf(Price::class, $price);
        $this->assertSame(199, $price->minorUnits);
        $this->assertSame('GBP', $price->currency->code());
    }

    public function test_get_returns_null_for_null(): void
    {
        $this->assertNull((new PriceCast())->get(new Product(), 'price', null, []));
    }

    public function test_set_accepts_a_price_instance(): void
    {
        $stored = (new PriceCast())->set(new Product(), 'price', new Price(199, new GBP()), []);

        $this->assertSame(199, $stored);
    }

    public function test_set_accepts_a_raw_int(): void
    {
        $this->assertSame(199, (new PriceCast())->set(new Product(), 'price', 199, []));
    }

    public function test_set_rejects_a_float(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PriceCast())->set(new Product(), 'price', 1.99, []);
    }

    public function test_set_rejects_a_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PriceCast())->set(new Product(), 'price', '1.99', []);
    }
}
