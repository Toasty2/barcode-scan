<?php

namespace Tests\Unit\Filament\Widgets;

use App\Filament\Widgets\YearlyOverview;
use App\Support\Money\Currencies\GBP;
use App\Support\Money\Price;
use Tests\TestCase;

class YearlyOverviewTest extends TestCase
{
    private function price(int $minorUnits): Price
    {
        return new Price($minorUnits, new GBP());
    }

    public function test_comparison_description_reports_more_spend(): void
    {
        $description = YearlyOverview::comparisonDescription(
            $this->price(15000),
            $this->price(10000),
            2025,
        );

        $this->assertSame('£50.00 more than 2025', $description);
    }

    public function test_comparison_description_reports_less_spend(): void
    {
        $description = YearlyOverview::comparisonDescription(
            $this->price(8000),
            $this->price(10000),
            2025,
        );

        $this->assertSame('£20.00 less than 2025', $description);
    }

    public function test_comparison_description_reports_no_change(): void
    {
        $description = YearlyOverview::comparisonDescription(
            $this->price(10000),
            $this->price(10000),
            2025,
        );

        $this->assertSame('Same as 2025', $description);
    }

    public function test_comparison_color_is_danger_for_more_spend(): void
    {
        $this->assertSame('danger', YearlyOverview::comparisonColor($this->price(15000), $this->price(10000)));
    }

    public function test_comparison_color_is_success_for_less_spend(): void
    {
        $this->assertSame('success', YearlyOverview::comparisonColor($this->price(8000), $this->price(10000)));
    }

    public function test_comparison_color_is_gray_for_no_change(): void
    {
        $this->assertSame('gray', YearlyOverview::comparisonColor($this->price(10000), $this->price(10000)));
    }
}
