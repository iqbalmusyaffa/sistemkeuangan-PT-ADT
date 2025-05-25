<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'invoice_date' => $this->invoice_date,
            'total_amount' => (float) $this->total_amount,
            'amount_paid' => (float) $this->amount_paid,
            'status' => $this->status,
            'notes' => $this->notes,
            'proyek' => $this->proyek ? [
                'id' => $this->proyek->id,
                'nama_proyek' => $this->proyek->nama_proyek,
                'nama_customer' => $this->proyek->nama_customer,
            ] : null,
            'purchase_materials' => $this->purchaseMaterials ? PurchaseMaterialResource::collection($this->purchaseMaterials) : [],
            'termins' => $this->termins ? TerminResource::collection($this->termins) : [],
            'expenses' => $this->expenses ? ExpenseResource::collection($this->expenses) : [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Tax information
            'use_ppn' => $this->use_ppn,
            'use_pph_non_final' => $this->use_pph_non_final,
            'use_pph_final' => $this->use_pph_final,
            'pph_non_final_amount' => (float) $this->pph_non_final_amount,
            'pph_final_amount' => (float) $this->pph_final_amount,
            'ppn_amount' => (float) $this->ppn_amount,
            'pph_jasa_amount' => (float) $this->pph_jasa_amount,
            'pph_barang_amount' => (float) $this->pph_barang_amount,
            'total_tax' => (float) $this->total_tax,
            'grand_total' => (float) $this->grand_total,

            // Profit/Loss information
            'profit_margin_percentage' => (float) $this->profit_margin_percentage,
            'net_profit' => (float) $this->net_profit,
            'total_income' => (float) $this->total_income,
            'total_expenses' => (float) $this->total_expenses,
            'profit_loss' => (float) $this->profit_loss,
            'profit_loss_percentage' => (float) $this->profit_loss_percentage,

            // Payment information
            'payment_method' => $this->paymentMethod ? [
                'id' => $this->paymentMethod->id,
                'name' => $this->paymentMethod->name
            ] : null,

            // Financial summary
            'financial_summary' => [
                'total_barang' => (float) $this->total_barang,
                'total_jasa' => (float) $this->total_jasa,
                'total_tax' => (float) $this->total_tax,
                'total_with_tax' => (float) $this->total_with_tax,
                'remaining_payment' => max(0, (float) ($this->grand_total ?? $this->total_amount) - (float) ($this->amount_paid ?? 0)),
                'remaining_payment_formatted' => 'Rp ' . number_format(max(0, (float) ($this->grand_total ?? $this->total_amount) - (float) ($this->amount_paid ?? 0)), 0, ',', '.'),
            ],

            // Project-level financial summary
            'project_financial_summary' => $this->getProjectFinancialSummary(),

            'summary' => [
                'total_termin' => (float) $this->termins->sum('nilai_termin'),
                'total_dp' => (float) $this->termins->sum('nilai_dp'),
                'total_pelunasan' => (float) $this->termins->sum('nilai_pelunasan'),
                'total_pembelian_material' => (float) $this->purchaseMaterials->sum('total_harga'),
                'total_dp_sudah_dibayar' => (float) \App\Models\Income::where('invoice_id', $this->id)->where('type', 'dp')->where('status', 'Diterima')->sum('jumlah'),
                'sisa_belum_dibayar' => (float) $this->termins->sum('nilai_termin') - ((float) $this->purchaseMaterials->sum('total_harga') + (float) \App\Models\Income::where('invoice_id', $this->id)->where('type', 'dp')->where('status', 'Diterima')->sum('jumlah')),
            ],
        ];
    }
}
