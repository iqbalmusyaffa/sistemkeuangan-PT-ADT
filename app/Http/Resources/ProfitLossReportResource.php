<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfitLossReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proyek' => $this->proyek ? [
                'id' => $this->proyek->id,
                'nama_proyek' => $this->proyek->nama_proyek,
            ] : null,
            'period_type' => $this->period_type,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'total_income' => (float) $this->total_income,
            'total_expense' => (float) $this->total_expense,
            'net_profit' => (float) $this->net_profit,
            'income_details' => $this->income_details ?? [],
            'expense_details' => $this->expense_details ?? [],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
