<?php

namespace App\Http\Resources\Billing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceListItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'invoice_id' => $this['invoice_id'] ?? null,
            'invoice_number' => $this['invoice_number'] ?? null,
            'date' => $this['date'] ?? null,
            'due_date' => $this['due_date'] ?? null,
            'status' => $this['status'] ?? null,
            'currency_code' => $this['currency_code'] ?? null,
            'total' => $this['total'] ?? null,
            'balance' => $this['balance'] ?? null,
            'customer_name' => $this['customer_name'] ?? null,
            'subscription_id' => $this['subscription_id'] ?? null,
            'invoice_url' => $this['invoice_url'] ?? null,
            'pdf_url' => $this['pdf_url'] ?? null,
            'created_time' => $this['created_time'] ?? null,
        ];
    }
}
