<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->first_name . ' ' . $this->last_name,
            'company' => $this->company,
            'phone' => $this->phone,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'country' => $this->country,
            'vat_number' => $this->vat_number,
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            
            // External IDs (only for admin)
            $this->mergeWhen($request->user()?->isAdmin() ?? false, [
                'plesk_user_id' => $this->plesk_user_id,
                'moneybird_contact_id' => $this->moneybird_contact_id,
            ]),
            
            // Relationships
            'orders' => OrderResource::collection($this->whenLoaded('orders')),
            'domains' => DomainResource::collection($this->whenLoaded('domains')),
        ];
    }
}
