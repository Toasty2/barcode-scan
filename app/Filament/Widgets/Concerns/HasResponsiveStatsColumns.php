<?php

namespace App\Filament\Widgets\Concerns;

trait HasResponsiveStatsColumns
{
    // Filament's own default StatsOverviewWidget::getColumns() picks one
    // column count and keeps it almost everywhere below the `xl` breakpoint
    // — so a 4-stat widget renders 4-across even on a phone, overflowing.
    // This stacks stats two-per-row below `lg`, widening out to one column
    // per stat from `lg` up. A single-stat widget (e.g. a "No budget set"
    // placeholder) naturally stays one column at every size.
    protected function getColumns(): int | array | null
    {
        $count = count($this->getCachedStats());

        return [
            'default' => min($count, 2),
            'lg' => $count,
        ];
    }
}
