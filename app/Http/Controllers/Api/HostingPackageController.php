<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HostingPackage;
use Illuminate\Http\JsonResponse;

class HostingPackageController extends Controller
{
    /**
     * Display a listing of active hosting packages.
     */
    public function index(): JsonResponse
    {
        $packages = HostingPackage::where('active', true)
            ->orderBy('price')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $packages,
        ]);
    }

    /**
     * Display the specified hosting package.
     */
    public function show(HostingPackage $package): JsonResponse
    {
        if (!$package->active) {
            return response()->json([
                'success' => false,
                'message' => 'Dit pakket is niet beschikbaar',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $package,
        ]);
    }
}
