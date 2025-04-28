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
            'proyek' => new ProyekResource($this->whenLoaded('proyek')),
            'purchase_materials' => PurchaseMaterialResource::collection($this->whenLoaded('purchaseMaterials')),
            'termins' => TerminResource::collection($this->whenLoaded('termins')),
            'expenses' => ExpenseResource::collection($this->whenLoaded('expenses')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
