<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AnnualBudgetAdherence;
use App\Filament\Widgets\AnnualOverview;
use App\Filament\Widgets\AnnualSpendByMonthChart;
use App\Filament\Widgets\AnnualSpendByShopChart;
use App\Filament\Widgets\YearlyOverview;
use App\Models\Trip;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use UnitEnum;

class AnnualSummary extends Page
{
    use HasFiltersForm;

    protected static string|UnitEnum|null $navigationGroup = 'Statistics';

    // A plain dash rather than a pictorial icon — subpages in this group
    // stay visually quiet rather than each competing for a distinct icon.
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMinus;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('year')
                ->label('Year')
                ->options(fn () => Trip::yearsWithTrips()->mapWithKeys(fn (int $year) => [$year => (string) $year])->all())
                ->default(Carbon::now()->year)
                ->selectablePlaceholder(false)
                ->required(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('previousYear')
                ->label('Previous year')
                ->icon(Heroicon::OutlinedChevronLeft)
                ->iconButton()
                ->disabled(fn () => ! $this->hasYear($this->selectedYear() - 1))
                ->action(fn () => $this->selectYear($this->selectedYear() - 1)),
            Action::make('nextYear')
                ->label('Next year')
                ->icon(Heroicon::OutlinedChevronRight)
                ->iconButton()
                ->disabled(fn () => ! $this->hasYear($this->selectedYear() + 1))
                ->action(fn () => $this->selectYear($this->selectedYear() + 1)),
        ];
    }

    public function content(Schema $schema): Schema
    {
        $shopChart = $this->getWidgetsSchemaComponents([AnnualSpendByShopChart::class]);

        if ($shopChart) {
            $shopChart[0]->key(fn () => 'annual-spend-by-shop-chart-'.$this->selectedYear());
        }

        return $schema->components([
            EmbeddedSchema::make('filtersForm'),
            Grid::make(2)->schema([
                ...$this->getWidgetsSchemaComponents([
                    AnnualOverview::class,
                    AnnualSpendByMonthChart::class,
                ]),
                ...$shopChart,
                ...$this->getWidgetsSchemaComponents([
                    AnnualBudgetAdherence::class,
                ]),
            ]),
            Text::make('Total spend by year')
                ->size(TextSize::Large)
                ->weight(FontWeight::Bold),
            Grid::make(1)->schema(
                $this->getWidgetsSchemaComponents([
                    YearlyOverview::class,
                ])
            ),
        ]);
    }

    private function selectedYear(): int
    {
        return (int) ($this->filters['year'] ?? Carbon::now()->year);
    }

    private function hasYear(int $year): bool
    {
        return Trip::yearsWithTrips()->contains($year);
    }

    private function selectYear(int $year): void
    {
        $this->filters['year'] = $year;
        $this->getFiltersForm()->fill($this->filters);
    }
}
