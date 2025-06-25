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
        $invoice = $this->whenLoaded('invoice', function () {
            return $this->invoice;
        });
        $proyek = $this->whenLoaded('proyek', function () {
            return $this->proyek;
        });

        $hasValidInvoice = $invoice && $invoice instanceof \App\Models\Invoice;

        $summary = [
            'total_termin' => $hasValidInvoice ? (float) $invoice->termins->sum('nilai_termin') : (float) $this->nilai_termin,
            'total_dp' => $hasValidInvoice ? (float) $invoice->termins->sum('nilai_dp') : (float) $this->nilai_dp,
            'total_pelunasan' => $hasValidInvoice ? (float) $invoice->termins->sum('nilai_pelunasan') : (float) $this->nilai_pelunasan,
            'total_dp_paid' => $hasValidInvoice ? (float) $invoice->termins->sum('total_dp_paid') : (float) $this->total_dp_paid,
            'total_pelunasan_paid' => $hasValidInvoice ? (float) $invoice->termins->sum('total_pelunasan_paid') : (float) $this->total_pelunasan_paid,
            'total_paid' => $hasValidInvoice ? (float) $invoice->termins->sum('total_paid') : (float) $this->total_paid,
            'remaining_dp' => $hasValidInvoice ? (float) $invoice->termins->sum('remaining_dp') : (float) $this->remaining_dp,
            'remaining_pelunasan' => $hasValidInvoice ? (float) $invoice->termins->sum('remaining_pelunasan') : (float) $this->remaining_pelunasan,
            'remaining_total' => $hasValidInvoice ? (float) $invoice->termins->sum('remaining_total') : (float) $this->remaining_total,
        ];

        if ($proyek && $proyek instanceof \App\Models\Proyek) {
            $summary['total_income'] = (float) $proyek->incomes()->where('status', 'Diterima')->sum('jumlah');
            $summary['total_expense'] = (float) $proyek->expenses()->where('status', 'Lunas')->sum('amount');
            $summary['profit_loss'] = $summary['total_income'] - $summary['total_expense'];
        }

        return [
            'id' => $this->id,
            'proyek_id' => $this->proyek_id,
            'invoice_id' => $this->invoice_id,
            'nama_termin' => $this->nama_termin,
            'project_progress' => (float) $this->project_progress,
            'target_progress' => (float) $this->target_progress,
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
            'status_termin' => $this->status_termin ?? 'Belum Dibayar',
            'status_approval' => $this->status_approval ?? 'Pending',
            'approved_by' => $this->approved_by,
            'approved_by_name' => $this->approvedByUser ? $this->approvedByUser->name : null,
            'approved_at' => optional($this->approved_at)->toDateTimeString(),
            'keterangan' => $this->keterangan,
            'bukti_pembayaran' => $this->bukti_pembayaran,
            'bukti_pembayaran_url' => $this->bukti_pembayaran
                ? asset('storage/' . $this->bukti_pembayaran)
                : null,
            'is_dp' => (bool) $this->is_dp,
            'is_pelunasan' => (bool) $this->is_pelunasan,
            'is_termin_bertahap' => (bool) $this->is_termin_bertahap,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
            'proyek' => $this->whenLoaded('proyek', function () {
                return $this->proyek ? new \App\Http\Resources\ProyekResource($this->proyek) : null;
            }),
            'invoice' => $this->whenLoaded('invoice', function () {
                return $this->invoice ? new \App\Http\Resources\InvoiceResource($this->invoice) : null;
            }),
            'incomes' => \App\Http\Resources\IncomeResource::collection($this->whenLoaded('incomes')),
            'financial_summary' => $summary,
            'invoice_status' => $this->invoice ? $this->invoice->status : null,
            'expense_status' => $this->expense ? $this->expense->status : null,
            'income_status' => $this->incomes->isNotEmpty()
                ? $this->incomes->pluck('status')->unique()->join(', ')
                : null,
        ];
    }
}
