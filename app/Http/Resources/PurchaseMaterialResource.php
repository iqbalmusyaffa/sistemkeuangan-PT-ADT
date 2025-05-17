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
            'unit' => new UnitResource($this->whenLoaded('unit')),
            'category' => new KategoriResource($this->whenLoaded('category')),
            'service_category' => new ServiceCategoryResource($this->whenLoaded('serviceCategory')),
            'qty' => $this->qty,
            'harga' => $this->harga,
            'total_harga' => $this->total_harga,
            'deskripsi' => $this->deskripsi,
            'merek' => new MerekResource($this->whenLoaded('merek')),
            'is_service' => $this->is_service,
            'invoice' => new InvoiceResource($this->whenLoaded('invoice')),
            'proyek' => new ProyekResource($this->whenLoaded('proyek')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
