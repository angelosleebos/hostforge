<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HostingPackageResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'price_monthly' => number_format((float) $this->price, 2),
            'price_yearly' => number_format((float) $this->price_yearly, 2),
            'billing_period' => $this->billing_period,
            'features' => [
                'disk_space' => $this->disk_space_mb . ' MB',
                'bandwidth' => $this->bandwidth_gb . ' GB',
                'email_accounts' => $this->email_accounts,
                'databases' => $this->databases,
                'domains' => $this->domains,
                'subdomains' => $this->subdomains,
            ],
            'active' => $this->active,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
