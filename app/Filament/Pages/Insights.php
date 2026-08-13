<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\InsightsOverview;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Insights extends Page
{
    // A plain dash rather than a pictorial icon — subpages in this group
    // stay visually quiet rather than each competing for a distinct icon.
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMinus;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Statistics');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InsightsOverview::class,
        ];
    }
}
