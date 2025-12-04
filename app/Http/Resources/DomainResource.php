<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DomainResource extends JsonResource
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
            'domain_name' => $this->domain_name,
            'tld' => $this->tld,
            'status' => $this->status,
            'registered_at' => $this->registered_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // External IDs (only for admin)
            $this->mergeWhen($request->user()?->isAdmin() ?? false, [
                'openprovider_domain_id' => $this->openprovider_domain_id,
                'plesk_domain_id' => $this->plesk_domain_id,
            ]),
        ];
    }
}
