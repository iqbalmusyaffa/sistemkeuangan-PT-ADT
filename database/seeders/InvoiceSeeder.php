<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\PurchaseMaterial;
use App\Models\Expense;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();

        try {
            // Dummy Data
            $proyekId = 1;
            $paymentMethodId = 2;

            $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . str_pad((Invoice::count() + 1), 4, '0', STR_PAD_LEFT);

            $invoice = Invoice::create([
                'proyek_id' => $proyekId,
                'payment_method_id' => $paymentMethodId,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => now()->toDateString(),
                'total_amount' => 0, // Akan dihitung ulang nanti
                'use_ppn' => true,
                'use_pph_non_final' => false,
                'use_pph_final' => false,
                'notes' => 'Dummy invoice seeder Juli 2025',
                'status' => 'unpaid',
                'amount_paid' => 0,
                'profit_margin_percentage' => 30.00,
            ]);

            // Dummy Purchase Materials
            $purchaseMaterials = [
                [
                    'item' => 'Semen Tiga Roda',
                    'type' => 'Material',
                    'spesifikasi' => 'Semen kuat tekan tinggi',
                    'deskripsi' => 'Untuk fondasi dan struktur',
                    'qty' => 100,
                    'harga' => 75000,
                    'unit_id' => 1,
                    'category_id' => 2,
                    'service_category_id' => null,
                    'merek_id' => 3,
                ],
                [
                    'item' => 'Jasa Tukang',
                    'type' => 'Service',
                    'spesifikasi' => 'Tenaga kerja tukang harian',
                    'deskripsi' => 'Pekerjaan pembangunan tembok',
                    'qty' => 10,
                    'harga' => 300000,
                    'unit_id' => 2,
                    'category_id' => null,
                    'service_category_id' => 1,
                    'merek_id' => null,
                ]
            ];

            foreach ($purchaseMaterials as $data) {
                $unit = Unit::find($data['unit_id']);
                $isService = $unit && in_array(strtolower($unit->unit_name), ['jasa', 'set', 'transaksi']);

                PurchaseMaterial::create([
                    'invoice_id' => $invoice->id,
                    'proyek_id' => $proyekId,
                    'item' => $data['item'],
                    'type' => $data['type'],
                    'qty' => $data['qty'],
                    'harga' => $data['harga'],
                    'unit_id' => $data['unit_id'],
                    'category_id' => $data['category_id'],
                    'service_category_id' => $data['service_category_id'],
                    'merek_id' => $data['merek_id'],
                    'spesifikasi' => $data['spesifikasi'],
                    'deskripsi' => $data['deskripsi'],
                    'total_harga' => $data['qty'] * $data['harga'],
                    'is_service' => $isService,
                ]);
            }

            // Dummy Direct Expenses
            $expenses = [
                [
                    'description' => 'Biaya transport material',
                    'amount' => 500000,
                    'category_id' => 4,
                    'service_category_id' => null,
                ],
                [
                    'description' => 'Uang makan tukang',
                    'amount' => 200000,
                    'category_id' => 4,
                    'service_category_id' => null,
                ]
            ];

            foreach ($expenses as $data) {
                Expense::create([
                    'proyek_id' => $proyekId,
                    'invoice_id' => $invoice->id,
                    'description' => $data['description'],
                    'amount' => $data['amount'],
                    'category_id' => $data['category_id'],
                    'service_category_id' => $data['service_category_id'],
                    'transaction_date' => now()->toDateString(),
                    'status' => Expense::STATUS_PENDING,
                    'payment_method_id' => $paymentMethodId,
                    'user_id' => 1,
                ]);
            }

            // Hitung ulang nilai invoice
            $invoice->refresh();
            $invoice->calculateAllFinancialValues();

            DB::commit();

            echo "✅ Dummy invoice berhasil disimpan dengan ID: {$invoice->id}\n";

        } catch (\Exception $e) {
            DB::rollBack();
            echo "❌ Gagal membuat dummy invoice: " . $e->getMessage() . "\n";
        }
    }
}
