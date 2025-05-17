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
            'total_amount' => $this->total_amount,
            'amount_paid' => $this->amount_paid,
            'status' => $this->status,
            'notes' => $this->notes,
            'proyek' => $this->proyek ? new ProyekResource($this->proyek) : null,
            'purchase_materials' => $this->purchaseMaterials ? PurchaseMaterialResource::collection($this->purchaseMaterials) : [],
            'termins' => $this->termins ? TerminResource::collection($this->termins) : [],
            'expenses' => $this->expenses ? ExpenseResource::collection($this->expenses) : [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Tax information
            'use_ppn' => $this->use_ppn,
            'use_pph_non_final' => $this->use_pph_non_final,
            'use_pph_final' => $this->use_pph_final,
            'pph_non_final_amount' => $this->pph_non_final_amount,
            'pph_final_amount' => $this->pph_final_amount,
            'ppn_amount' => $this->ppn_amount,
            'pph_jasa_amount' => $this->pph_jasa_amount,
            'pph_barang_amount' => $this->pph_barang_amount,

            // Profit/Loss information
            'profit_margin_percentage' => $this->profit_margin_percentage,
            'net_profit' => $this->net_profit,
            'total_income' => $this->total_income,
            'total_expenses' => $this->total_expenses,
            'profit_loss' => $this->profit_loss,
            'profit_loss_percentage' => $this->profit_loss_percentage,

            // Payment information
            'payment_method' => $this->paymentMethod ? [
                'id' => $this->paymentMethod->id,
                'name' => $this->paymentMethod->name
            ] : null,

            // Financial summary
            'financial_summary' => [
                'total_barang' => $this->total_barang,
                'total_jasa' => $this->total_jasa,
                'total_tax' => $this->total_tax,
                'total_with_tax' => $this->total_with_tax,
                'remaining_payment' => $this->total_amount - $this->amount_paid
            ],

            // Project-level financial summary
            'project_financial_summary' => $this->getProjectFinancialSummary()
        ];
    }
}
