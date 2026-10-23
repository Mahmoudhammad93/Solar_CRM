<?php

namespace Tests\Feature\Visits;

use App\Models\Document;
use App\Models\User;
use App\Models\Visit;
use App\Services\DocumentService;
use App\Services\NumberingService;
use App\Services\VisitReportService;
use App\Support\RecordScoping;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SequenceSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VisitScopingAndDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
        $this->seed(SequenceSeeder::class);
        $this->seed(LookupSeeder::class);
        Storage::fake('local');
        // Dompdf needs a real local path for some ops — use real disk for reports in PDF test
    }

    public function test_surveyor_cannot_view_unassigned_visit(): void
    {
        $surveyorA = User::factory()->withRole('surveyor-engineer')->create(['email' => 'surv-a@test']);
        $surveyorB = User::factory()->withRole('surveyor-engineer')->create(['email' => 'surv-b@test']);

        $visit = Visit::query()->create([
            'reference' => app(NumberingService::class)->next('visit'),
            'scheduled_at' => now()->addDay(),
            'assigned_surveyor_id' => $surveyorB->id,
            'status' => 'scheduled',
            'site_address' => 'Demo site',
        ]);

        $this->assertFalse(RecordScoping::canAccessOwnedRecord($surveyorA, $visit->assigned_surveyor_id));

        $response = $this->actingAs($surveyorA)->get('/admin/visits/'.$visit->id.'/edit');
        $this->assertContains($response->status(), [403, 404]);
    }

    public function test_unauthorized_document_download_returns_403(): void
    {
        Storage::fake('local');
        $surveyorA = User::factory()->withRole('surveyor-engineer')->create();
        $surveyorB = User::factory()->withRole('surveyor-engineer')->create();
        $visit = Visit::query()->create([
            'reference' => app(NumberingService::class)->next('visit'),
            'scheduled_at' => now(),
            'assigned_surveyor_id' => $surveyorB->id,
            'status' => 'scheduled',
        ]);

        $file = UploadedFile::fake()->image('site.jpg');
        $doc = app(DocumentService::class)->store($visit, $file, $surveyorB, [
            'document_type' => 'site_photo',
        ]);

        $this->actingAs($surveyorA)
            ->get(route('documents.download', $doc->uuid))
            ->assertForbidden();
    }

    public function test_disallowed_mime_php_renamed_as_jpg_is_rejected(): void
    {
        Storage::fake('local');
        $user = User::factory()->withRole('super-admin')->create();
        $visit = Visit::query()->create([
            'reference' => app(NumberingService::class)->next('visit'),
            'scheduled_at' => now(),
            'assigned_surveyor_id' => $user->id,
            'status' => 'scheduled',
        ]);

        $tmp = tempnam(sys_get_temp_dir(), 'evil');
        file_put_contents($tmp, "<?php echo 'hack';");
        $upload = new UploadedFile($tmp, 'photo.jpg', 'image/jpeg', null, true);

        $this->expectException(ValidationException::class);
        app(DocumentService::class)->store($visit, $upload, $user, ['document_type' => 'site_photo']);
    }

    public function test_visit_report_pdf_is_generated_and_non_empty(): void
    {
        // Use real local disk for Dompdf output path
        config(['filesystems.disks.local.root' => storage_path('app/private')]);

        $user = User::factory()->withRole('super-admin')->create();
        $visit = Visit::query()->create([
            'reference' => app(NumberingService::class)->next('visit'),
            'scheduled_at' => now(),
            'assigned_surveyor_id' => $user->id,
            'status' => 'completed',
            'site_address' => '12 Demo Road',
            'gps_lat' => 31.52,
            'gps_lng' => 74.35,
            'property_type' => 'residential',
            'roof_type_material' => 'RCC flat',
            'usable_area_sqm' => 40,
            'feasibility' => 'feasible',
            'preliminary_bom' => '4x 550W panels (text)',
            'completed_at' => now(),
        ]);

        [$binary, $filename, $absolutePath] = app(VisitReportService::class)->generate($visit);

        $this->assertNotSame('', $binary);
        $this->assertGreaterThan(500, strlen($binary));
        $this->assertStringStartsWith('%PDF', $binary);
        $this->assertFileExists($absolutePath);
        $this->assertGreaterThan(500, filesize($absolutePath));
        $this->assertStringEndsWith('.pdf', $filename);
    }

    public function test_assigned_surveyor_can_download_own_visit_document(): void
    {
        Storage::fake('local');
        $surveyor = User::factory()->withRole('surveyor-engineer')->create();
        $visit = Visit::query()->create([
            'reference' => app(NumberingService::class)->next('visit'),
            'scheduled_at' => now(),
            'assigned_surveyor_id' => $surveyor->id,
            'status' => 'scheduled',
        ]);

        $file = UploadedFile::fake()->image('meter.png');
        $doc = app(DocumentService::class)->store($visit, $file, $surveyor, [
            'document_type' => 'electrical_room_photo',
        ]);

        $this->actingAs($surveyor)
            ->get(route('documents.download', $doc->uuid))
            ->assertOk();
    }
}
