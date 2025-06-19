<?php

namespace App\Http\Controllers;

use App\Models\PostalServer;
use App\Services\PostalService;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class StatsController extends Controller
{
    use ApiResponseTrait;

    protected PostalService $postalService;

    public function __construct(PostalService $postalService)
    {
        $this->postalService = $postalService;
    }

    public function server(Request $request, PostalServer $postalServer): JsonResponse
    {
        try {
            if (!$postalServer->is_active) {
                return $this->errorResponse('Server is not active', 422);
            }

            $validated = $request->validate([
                'period' => 'nullable|string|in:1d,7d,14d,30d,today,yesterday',
            ]);

            $stats = $this->postalService->getServerStats($postalServer, $validated);
            
            return response()->json($stats);
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->productionSafeErrorResponse($e, 'Failed to retrieve server statistics', 500);
        }
    }

    public function bounces(Request $request, PostalServer $postalServer): JsonResponse
    {
        try {
            if (!$postalServer->is_active) {
                return $this->errorResponse('Server is not active', 422);
            }

            $validated = $request->validate([
                'period' => 'nullable|string|in:1d,7d,14d,30d,today,yesterday'
            ]);

            $bounceData = $this->postalService->getBounceData($postalServer, $validated);
            
            return $this->successResponse($bounceData, 'Bounce data retrieved successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->productionSafeErrorResponse($e, 'Failed to retrieve bounce data', 500);
        }
    }

    public function bouncesByDomain(Request $request, PostalServer $postalServer): JsonResponse
    {
        try {
            if (!$postalServer->is_active) {
                return $this->errorResponse('Server is not active', 422);
            }

            $validated = $request->validate([
                'period' => 'nullable|string|in:1d,7d,14d,30d,today,yesterday',
                'per_page' => 'nullable|integer|min:1|max:100',
                'q' => 'nullable|string|max:255'
            ]);

            $perPage = min($validated['per_page'] ?? 15, 100);
            $bouncesByDomain = $this->postalService->getBouncesByDomain($postalServer, $validated, $perPage);
            
            return $this->successResponse([
                'data' => $bouncesByDomain->items(),
                'pagination' => [
                    'current_page' => $bouncesByDomain->currentPage(),
                    'last_page' => $bouncesByDomain->lastPage(),
                    'per_page' => $bouncesByDomain->perPage(),
                    'total' => $bouncesByDomain->total(),
                    'from' => $bouncesByDomain->firstItem(),
                    'to' => $bouncesByDomain->lastItem()
                ]
            ], 'Bounce statistics by domain retrieved successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->productionSafeErrorResponse($e, 'Failed to retrieve bounce statistics by domain', 500);
        }
    }

    public function bouncesByAddress(Request $request, PostalServer $postalServer): JsonResponse
    {
        try {
            if (!$postalServer->is_active) {
                return $this->errorResponse('Server is not active', 422);
            }

            $validated = $request->validate([
                'period' => 'nullable|string|in:1d,7d,14d,30d,today,yesterday',
                'per_page' => 'nullable|integer|min:1|max:100',
                'q' => 'nullable|string|max:255'
            ]);

            $perPage = min($validated['per_page'] ?? 15, 100);
            $bouncesByAddress = $this->postalService->getBouncesByAddress($postalServer, $validated, $perPage);
            
            return $this->successResponse([
                'data' => $bouncesByAddress->items(),
                'pagination' => [
                    'current_page' => $bouncesByAddress->currentPage(),
                    'last_page' => $bouncesByAddress->lastPage(),
                    'per_page' => $bouncesByAddress->perPage(),
                    'total' => $bouncesByAddress->total(),
                    'from' => $bouncesByAddress->firstItem(),
                    'to' => $bouncesByAddress->lastItem()
                ]
            ], 'Bounce statistics by address retrieved successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->productionSafeErrorResponse($e, 'Failed to retrieve bounce statistics by address', 500);
        }
    }
}
