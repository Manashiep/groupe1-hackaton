<?php

namespace Tests\Feature\Filament\Resources\Reports;

use App\Enums\ReportStatus;
use App\Filament\Resources\Reports\Pages\ManageReports;
use App\Models\Report;
use App\Models\Truck;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizens_cannot_open_the_admin_panel(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/signalements')
            ->assertForbidden();
    }

    public function test_responsable_takes_a_report_in_charge_with_a_truck_and_time(): void
    {
        $report = Report::factory()->create();
        $truck = Truck::factory()->create();
        $cleanupAt = today()->addDay()->setTime(9, 30);
        $this->actingAs(User::factory()->responsable()->create());

        Livewire::test(ManageReports::class)
            ->callAction(TestAction::make('assign')->table($report), data: [
                'truck_id' => $truck->id,
                'cleanup_at' => $cleanupAt,
            ])
            ->assertHasNoFormErrors();

        $report->refresh();
        $this->assertSame(ReportStatus::PrisEnCharge, $report->status);
        $this->assertTrue($report->truck->is($truck));
        $this->assertTrue($report->cleanup_at->equalTo($cleanupAt));
    }

    public function test_rejecting_a_report_requires_a_reason(): void
    {
        $report = Report::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageReports::class)
            ->callAction(TestAction::make('reject')->table($report), data: ['admin_note' => ''])
            ->assertHasFormErrors(['admin_note' => 'required']);

        $this->assertSame(ReportStatus::Nouveau, $report->fresh()->status);
    }
}
