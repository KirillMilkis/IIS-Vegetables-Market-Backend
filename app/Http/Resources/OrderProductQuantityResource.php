<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderProductQuantityResource extends JsonResource
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
            'orderId' => $this->order_id,
            'productId' => $this->product_id,
            'quantity' => $this->quantity,
            'quantityType' => $this->quantity_type,
            'price' => $this->price,
            'status' => $this->status,
            'productName' => $this->product_name
        ];
    }
}
