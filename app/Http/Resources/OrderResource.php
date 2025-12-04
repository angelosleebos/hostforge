<?php

declare(strict_types=1);

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
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'subtotal' => number_format((float) $this->subtotal, 2),
            'tax' => number_format((float) $this->tax, 2),
            'total' => number_format((float) $this->total, 2),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'provisioned_at' => $this->provisioned_at?->toIso8601String(),
            'activated_at' => $this->activated_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Relationships
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'hosting_package' => new HostingPackageResource($this->whenLoaded('hostingPackage')),
            'domains' => DomainResource::collection($this->whenLoaded('domains')),

            // Moneybird integration
            'moneybird_invoice_id' => $this->moneybird_invoice_id,
        ];
    }
}
