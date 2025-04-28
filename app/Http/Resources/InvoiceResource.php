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
        ];
    }
}
