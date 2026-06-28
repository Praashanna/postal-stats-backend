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

    public function suppressions(Request $request, PostalServer $postalServer): JsonResponse
    {
        try {
            if (!$postalServer->is_active) {
                return $this->errorResponse('Server is not active', 422);
            }

            $validated = $request->validate([
                'per_page' => 'nullable|integer|min:1|max:100',
                'q' => 'nullable|string|max:255',
                'domain' => 'nullable|string|max:255',
            ]);

            $perPage = min($validated['per_page'] ?? 15, 100);
            $suppressions = $this->postalService->getSuppressions($postalServer, $validated, $perPage);

            return $this->successResponse([
                'data' => $suppressions->items(),
                'pagination' => [
                    'current_page' => $suppressions->currentPage(),
                    'last_page' => $suppressions->lastPage(),
                    'per_page' => $suppressions->perPage(),
                    'total' => $suppressions->total(),
                    'from' => $suppressions->firstItem(),
                    'to' => $suppressions->lastItem(),
                ]
            ], 'Suppressions retrieved successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->productionSafeErrorResponse($e, 'Failed to retrieve suppressions', 500);
        }
    }

    public function deleteSuppressions(Request $request, PostalServer $postalServer): JsonResponse
    {
        try {
            if (!$postalServer->is_active) {
                return $this->errorResponse('Server is not active', 422);
            }

            $validated = $request->validate([
                'scope' => 'nullable|string|in:all,domain,preset,address',
                'domain' => 'nullable|string|max:255',
                'domains' => 'nullable|array',
                'domains.*' => 'string|max:255',
                'preset' => 'nullable|string|in:google,microsoft,yahoo',
                'address' => 'nullable|string|max:255',
            ]);

            $deleted = $this->postalService->removeSuppressions($postalServer, $validated);

            return $this->successResponse([
                'deleted' => $deleted,
            ], 'Suppressions removed successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->productionSafeErrorResponse($e, 'Failed to remove suppressions', 500);
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

    public function bouncesByErrorType(Request $request, PostalServer $postalServer): JsonResponse
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
            $bouncesByErrorType = $this->postalService->getBouncesByErrorType($postalServer, $validated, $perPage);

            return $this->successResponse([
                'data' => $bouncesByErrorType->items(),
                'pagination' => [
                    'current_page' => $bouncesByErrorType->currentPage(),
                    'last_page' => $bouncesByErrorType->lastPage(),
                    'per_page' => $bouncesByErrorType->perPage(),
                    'total' => $bouncesByErrorType->total(),
                    'from' => $bouncesByErrorType->firstItem(),
                    'to' => $bouncesByErrorType->lastItem()
                ]
            ], 'Bounce statistics by error type retrieved successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->productionSafeErrorResponse($e, 'Failed to retrieve bounce statistics by error type', 500);
        }
    }

    public function suppressBouncesByErrorType(Request $request, PostalServer $postalServer): JsonResponse
    {
        try {
            if (!$postalServer->is_active) {
                return $this->errorResponse('Server is not active', 422);
            }

            $validated = $request->validate([
                'error_type' => 'required|string|max:255',
                'duration' => 'required|string|in:7d,1m,1y,infinite',
                'period' => 'nullable|string|in:1d,7d,14d,30d,today,yesterday',
            ]);

            $result = $this->postalService->suppressBounceAddressesByErrorType($postalServer, $validated);

            return $this->successResponse($result, 'Bounce addresses suppressed successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->productionSafeErrorResponse($e, 'Failed to suppress bounce addresses by error type', 500);
        }
    }
}
