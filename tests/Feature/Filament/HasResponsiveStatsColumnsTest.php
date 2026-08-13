<?php

namespace Tests\Feature\Filament;

use App\Filament\Widgets\ProductOverview;
use App\Filament\Widgets\SpendOverview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class HasResponsiveStatsColumnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_zero_stat_widget_stays_a_single_empty_column_at_every_size(): void
    {
        // ProductOverview::getStats() returns [] with no record set — covers
        // the same min() floor a genuine 1-stat widget (e.g. "No budget
        // set") would hit, without needing any seeded data.
        $widget = new ProductOverview();

        $columns = (new ReflectionMethod($widget, 'getColumns'))->invoke($widget);

        $this->assertSame(['default' => 0, 'lg' => 0], $columns);
    }

    public function test_a_two_stat_widget_uses_two_columns_at_every_size(): void
    {
        $widget = new SpendOverview();

        $columns = (new ReflectionMethod($widget, 'getColumns'))->invoke($widget);

        $this->assertSame(['default' => 2, 'lg' => 2], $columns);
    }
}
