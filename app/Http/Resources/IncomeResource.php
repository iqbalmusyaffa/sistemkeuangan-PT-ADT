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
            'kategori' => new KategoriResource($this->whenLoaded('kategori')),
            'payment_method_id' => $this->payment_method_id,
            'payment_method' => new PaymentMethodResource($this->whenLoaded('paymentMethod')),
            'proyek_id' => $this->proyek_id,
            'proyek' => new ProyekResource($this->whenLoaded('proyek')),
            'termin_id' => $this->termin_id,
            'termin' => new TerminResource($this->whenLoaded('termin')),
            'type' => $this->type,
            'jumlah' => $this->jumlah,
            'jumlah_pembayaran' => $this->jumlah_pembayaran, // <--- Tambahan ini penting!
            'formatted_amount' => $this->formatted_amount,
            'deskripsi' => $this->deskripsi,
            'tanggal' => $this->tanggal,
            'formatted_date' => $this->formatted_date,
            'status' => $this->status,
            'bukti' => $this->bukti_pembayaran,
           'bukti_pembayaran_url' => $this->bukti_pembayaran_url,


            'created_by' => $this->created_by,
            'created_by_user' => new UserResource($this->whenLoaded('creator')),
            'updated_by' => $this->updated_by,
            'updated_by_user' => new UserResource($this->whenLoaded('updater')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
