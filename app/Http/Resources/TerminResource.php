<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TerminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proyek_id' => $this->proyek_id,
            'invoice_id' => $this->invoice_id,
            'nama_termin' => $this->nama_termin,
            'jenis_termin' => $this->jenis_termin,
            'termin_ke' => $this->termin_ke,
            'nilai_termin' => (float) $this->nilai_termin,
            'persentase_dp' => (float) $this->persentase_dp,
            'nilai_dp' => (float) $this->nilai_dp,
            'nilai_pelunasan' => (float) $this->nilai_pelunasan,
            'total_dp_paid' => (float) $this->total_dp_paid,
            'total_pelunasan_paid' => (float) $this->total_pelunasan_paid,
            'total_paid' => (float) $this->total_paid,
            'remaining_dp' => (float) $this->remaining_dp,
            'remaining_pelunasan' => (float) $this->remaining_pelunasan,
            'remaining_total' => (float) $this->remaining_total,
            'tanggal_dp' => optional($this->tanggal_dp)->toDateString(),
            'tanggal_pelunasan' => optional($this->tanggal_pelunasan)->toDateString(),
            'tanggal_dp_dibayar' => optional($this->tanggal_dp_dibayar)->toDateString(),
            'tanggal_pelunasan_dibayar' => optional($this->tanggal_pelunasan_dibayar)->toDateString(),
            'status_termin' => $this->status_termin,
            'status_approval' => $this->status_approval,
            'approved_by' => $this->approved_by,
            'approved_by_name' => $this->approvedByUser ? $this->approvedByUser->name : null,
            'approved_at' => optional($this->approved_at)->toDateTimeString(),
            'keterangan' => $this->keterangan,

            // 👇 Tambahan bukti pembayaran
            'bukti_pembayaran' => $this->bukti_pembayaran,
            'bukti_pembayaran_url' => $this->bukti_pembayaran
                ? asset('storage/' . $this->bukti_pembayaran)
                : null,

            // Boolean helpers dari model
            'is_dp' => (bool) $this->is_dp,
            'is_pelunasan' => (bool) $this->is_pelunasan,
            'is_termin_bertahap' => (bool) $this->is_termin_bertahap,

            // Waktu pembuatan dan update
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),

            // Relasi (hanya jika sudah eager loaded)
            'proyek' => $this->whenLoaded('proyek', function() {
                return $this->proyek ? new \App\Http\Resources\ProyekResource($this->proyek) : null;
            }),
            'invoice' => $this->whenLoaded('invoice', function() {
                return $this->invoice ? new \App\Http\Resources\InvoiceResource($this->invoice) : null;
            }),
            'incomes' => \App\Http\Resources\IncomeResource::collection($this->whenLoaded('incomes')),
        ];
    }
}
