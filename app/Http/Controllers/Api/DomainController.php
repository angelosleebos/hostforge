<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckDomainRequest;
use App\Services\OpenProviderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DomainController extends Controller
{
    public function __construct(
        private OpenProviderService $openProviderService
    ) {}

    /**
     * Check domain availability.
     */
    public function check(CheckDomainRequest $request): JsonResponse
    {
        try {
            $domain = $request->validated('domain');
            $isAvailable = $this->openProviderService->checkDomainAvailability($domain);

            return response()->json([
                'success' => true,
                'data' => [
                    'domain' => $domain,
                    'available' => $isAvailable,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Domain availability check failed', [
                'domain' => $request->validated('domain'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Kon beschikbaarheid niet controleren',
            ], 500);
        }
    }

    /**
     * Get domain pricing for different TLDs.
     */
    public function pricing(): JsonResponse
    {
        try {
            $pricing = $this->openProviderService->getDomainPricing();

            return response()->json([
                'success' => true,
                'data' => $pricing,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch domain pricing', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Kon prijzen niet ophalen',
            ], 500);
        }
    }
}
