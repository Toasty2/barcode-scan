<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\AnnualSummary;
use App\Filament\Widgets\YearlyOverview;
use App\Models\Trip;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class AnnualSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/annual-summary')->assertRedirect('/admin/login');
    }

    public function test_it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(AnnualSummary::class)->assertSuccessful();
    }

    public function test_yearly_overview_is_not_registered_as_a_dashboard_widget(): void
    {
        $this->assertNotContains(YearlyOverview::class, Filament::getWidgets());
    }

    public function test_year_filter_defaults_to_the_current_year(): void
    {
        Carbon::setTestNow('2026-08-13');
        Trip::create(['shopped_on' => '2024-01-01', 'discount' => 0]);

        $this->actingAs(User::factory()->create());

        $component = Livewire::test(AnnualSummary::class);

        $this->assertSame(2026, (int) $component->get('filters')['year']);
    }

    public function test_next_year_action_is_disabled_at_the_current_year(): void
    {
        Carbon::setTestNow('2026-08-13');
        Trip::create(['shopped_on' => '2024-01-01', 'discount' => 0]);

        $this->actingAs(User::factory()->create());

        Livewire::test(AnnualSummary::class)->assertActionDisabled('nextYear');
    }

    public function test_previous_year_action_is_disabled_at_the_earliest_year_with_trips(): void
    {
        Carbon::setTestNow('2026-08-13');
        Trip::create(['shopped_on' => '2025-01-01', 'discount' => 0]);

        $this->actingAs(User::factory()->create());

        $component = Livewire::test(AnnualSummary::class);
        $component->assertActionEnabled('previousYear');

        $component->callAction('previousYear');
        $this->assertSame(2025, (int) $component->get('filters')['year']);

        $component->assertActionDisabled('previousYear');
    }

    public function test_selecting_the_previous_year_updates_the_filter(): void
    {
        Carbon::setTestNow('2026-08-13');
        Trip::create(['shopped_on' => '2025-01-01', 'discount' => 0]);

        $this->actingAs(User::factory()->create());

        $component = Livewire::test(AnnualSummary::class);
        $component->callAction('previousYear');

        $this->assertSame(2025, (int) $component->get('filters')['year']);
    }
}
