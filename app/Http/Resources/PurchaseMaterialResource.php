<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseMaterialResource extends JsonResource
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
            'invoice_id' => $this->invoice_id,
            'proyek_id' => $this->proyek_id,
            'item' => $this->item,
            'type' => $this->type,
            'spesifikasi' => $this->spesifikasi,
            'unit' => $this->unit ? new UnitResource($this->unit) : null,
            'category' => $this->category ? new KategoriResource($this->category) : null,
            'service_category' => $this->serviceCategory ? new ServiceCategoryResource($this->serviceCategory) : null,
            'qty' => (float) $this->qty,
            'harga' => (float) $this->harga,
            'total_harga' => (float) $this->total_harga,
            'deskripsi' => $this->deskripsi,
            'merek' => $this->merek ? new MerekResource($this->merek) : null,
            'is_service' => (bool) $this->is_service,
            'expense_id' => $this->expense_id,
            'expense' => new ExpenseResource($this->whenLoaded('expense')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
