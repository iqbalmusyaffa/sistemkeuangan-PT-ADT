<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
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
            'kode_transaksi' => $this->kode_transaksi,
            'amount' => $this->amount,
            'description' => $this->description,
            'transaction_date' => $this->transaction_date ? $this->transaction_date->format('Y-m-d') : null,
            'status' => $this->status,
            'category_name' => $this->category_name,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'service_category' => new ServiceCategoryResource($this->whenLoaded('serviceCategory')),
            // 'proyek' removed to prevent circular reference
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'prepared_fund' => $this->prepared_fund,
            'invoice_id' => $this->invoice_id,
            // tambahkan field lain sesuai kebutuhan frontend
        ];
    }
}
