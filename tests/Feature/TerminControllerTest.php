<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\Proyek;
use App\Models\Invoice;
use App\Models\Termin;

class TerminControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $proyek;
    protected $invoice;
    protected $terminData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active'
        ]);

        $this->proyek = Proyek::factory()->create(['anggaran_kontrak' => 100000000]);

        $this->invoice = Invoice::factory()->create([
            'proyek_id' => $this->proyek->id,
            'grand_total' => 1000000,
            'status' => 'unpaid'
        ]);

        $this->terminData = [
            'proyek_id' => $this->proyek->id,
            'invoice_id' => $this->invoice->id,
            'nama_termin' => 'Termin 1',
            'jenis_termin' => 'DP',
            'target_progress' => 20,
            'termin_ke' => 1,
            'nilai_termin' => 500000,
            'persentase_dp' => 20,
            'tanggal_dp' => now()->format('Y-m-d'),
            'keterangan' => 'Test Termin'
        ];
    }

    public function test_can_create_termin()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/termins', $this->terminData);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'nama_termin' => 'Termin 1',
                    'jenis_termin' => 'DP',
                    'termin_ke' => 1,
                    'nilai_termin' => 500000,
                    'persentase_dp' => 20,
                    'status_termin' => 'Belum Dibayar',
                    'status_approval' => 'Pending'
                ]
            ]);

        $this->assertDatabaseHas('termins', [
            'nama_termin' => 'Termin 1',
            'jenis_termin' => 'DP',
            'termin_ke' => 1,
            'nilai_termin' => 500000
        ]);
    }

    public function test_can_get_single_termin()
    {
        $this->actingAs($this->user);

        $termin = Termin::factory()->create([
            'proyek_id' => $this->proyek->id,
            'invoice_id' => $this->invoice->id,
            'nama_termin' => 'Termin 1',
            'jenis_termin' => 'DP',
            'target_progress' => 20,
            'termin_ke' => 1,
            'nilai_termin' => 500000,
            'persentase_dp' => 20,
            'status_termin' => 'Belum Dibayar',
            'status_approval' => 'Pending'
        ]);

        $response = $this->getJson('/api/termins/' . $termin->id);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $termin->id,
                    'nama_termin' => 'Termin 1'
                ]
            ]);
    }

    public function test_can_update_termin()
    {
        $this->actingAs($this->user);

        $termin = Termin::factory()->create([
            'proyek_id' => $this->proyek->id,
            'invoice_id' => $this->invoice->id,
            'nama_termin' => 'Termin 1',
            'jenis_termin' => 'DP',
            'target_progress' => 20,
            'termin_ke' => 1,
            'nilai_termin' => 500000,
            'persentase_dp' => 20,
        ]);

        $updateData = [
            'proyek_id' => $this->proyek->id,
            'invoice_id' => $this->invoice->id,
            'nama_termin' => 'Termin Updated',
            'jenis_termin' => 'DP',
            'target_progress' => 25,
            'termin_ke' => 1,
            'nilai_termin' => 600000,
            'persentase_dp' => 25,
            'tanggal_dp' => now()->format('Y-m-d'),
            'keterangan' => 'Updated Description'
        ];

        $response = $this->putJson('/api/termins/' . $termin->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'nama_termin' => 'Termin Updated',
                    'nilai_termin' => 600000
                ]
            ]);
    }

    public function test_can_delete_termin()
    {
        $this->actingAs($this->user);

        $termin = Termin::factory()->create([
            'proyek_id' => $this->proyek->id,
            'invoice_id' => $this->invoice->id
        ]);

        $response = $this->deleteJson('/api/termins/' . $termin->id);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Termin berhasil dihapus']);

        $this->assertDatabaseMissing('termins', [
            'id' => $termin->id
        ]);
    }

    public function test_validation_error_on_termin_create()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/termins', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'proyek_id',
                'invoice_id',
                'nama_termin',
                'jenis_termin',
                'target_progress',
                'nilai_termin',
                'persentase_dp'
            ]);
    }

    public function test_validation_error_on_termin_update()
    {
        $this->actingAs($this->user);

        $termin = Termin::factory()->create([
            'proyek_id' => $this->proyek->id,
            'invoice_id' => $this->invoice->id
        ]);

        $invalidData = [
            'nama_termin' => '',
            'jenis_termin' => 'invalid',
            'target_progress' => 200,
            'nilai_termin' => -100,
            'persentase_dp' => 110
        ];

        $response = $this->putJson('/api/termins/' . $termin->id, $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'nama_termin',
                'jenis_termin',
                'target_progress',
                'nilai_termin',
                'persentase_dp'
            ]);
    }

    public function test_unique_termin_ke_per_jenis_per_invoice()
    {
        $this->actingAs($this->user);

        Termin::factory()->create([
            'proyek_id' => $this->proyek->id,
            'invoice_id' => $this->invoice->id,
            'jenis_termin' => 'DP',
            'termin_ke' => 1
        ]);

        $duplicate = $this->terminData;
        $duplicate['jenis_termin'] = 'DP'; // same jenis + termin_ke

        $response = $this->postJson('/api/termins', $duplicate);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Termin ke-1 dengan jenis DP sudah ada untuk proyek dan invoice ini.'
            ]);
    }
}
