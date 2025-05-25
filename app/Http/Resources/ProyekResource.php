<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProyekResource extends JsonResource
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
            'nama_proyek' => $this->nama_proyek,
            'kode_proyek' => $this->kode_proyek,
            'klien' => $this->klien,
            'tanggal_mulai' => $this->tanggal_mulai,
            'tanggal_selesai' => $this->tanggal_selesai,
            'status' => $this->status,
            'budget' => (float) $this->budget,
            'budget_adjusted' => (float) $this->budget_adjusted,
            'total_pengeluaran' => (float) $this->total_pengeluaran,
            'sisa_anggaran' => (float) $this->sisa_anggaran,
            'budget_percentage' => (float) $this->budget_percentage,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Uncomment if you want to include related data:
            // 'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
            // 'termins' => TerminResource::collection($this->whenLoaded('termins')),
            // 'purchase_materials' => PurchaseMaterialResource::collection($this->whenLoaded('purchaseMaterials')),
        ];
    }
}
