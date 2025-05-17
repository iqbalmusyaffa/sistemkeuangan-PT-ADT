<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncomeResource extends JsonResource
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
            'kategori_id' => $this->kategori_id,
            'kategori' => $this->whenLoaded('kategori'),
            'payment_method_id' => $this->payment_method_id,
            'payment_method' => $this->whenLoaded('paymentMethod'),
            'proyek_id' => $this->proyek_id,
            'proyek' => $this->whenLoaded('proyek'),
            'termin_id' => $this->termin_id,
            'termin' => $this->whenLoaded('termin'),
            'type' => $this->type,
            'jumlah' => $this->jumlah,
            'deskripsi' => $this->deskripsi,
            'tanggal' => $this->tanggal,
            'status' => $this->status,
            'bukti_pembayaran' => $this->bukti_pembayaran,
            'created_by' => $this->created_by,
            'created_by_user' => $this->whenLoaded('createdByUser'),
            'updated_by' => $this->updated_by,
            'updated_by_user' => $this->whenLoaded('updatedByUser'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 