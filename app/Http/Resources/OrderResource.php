<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'totalPrice' => $this->total_price,
            'orderDate' => $this->order_date,
            'orderStatus' => $this->order_status,
            'customerId' => $this->customer_id,
        ];
    }
}
