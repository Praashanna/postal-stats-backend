<?php

namespace App\Http\Controllers;

use App\Models\PostalServer;
use App\Services\PostalServerService;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Resources\PostalServerResource;
use Illuminate\Http\JsonResponse;

class PostalServerController extends Controller
{
    use ApiResponseTrait;

    protected PostalServerService $postalServerService;

    public function __construct(PostalServerService $postalServerService)
    {
        $this->postalServerService = $postalServerService;
    }

    public function index(): JsonResponse
    {
        try {
            $servers = $this->postalServerService->getAllServers();
            return $this->successResponse(
                PostalServerResource::collection($servers),
                'Postal servers retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->productionSafeErrorResponse($e, 'Failed to retrieve postal servers', 500);
        }
    }

    public function show(PostalServer $postalServer): JsonResponse
    {
        try {
            return $this->successResponse(
                new PostalServerResource($postalServer->loadMissing('organization')),
                'Postal server retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->productionSafeErrorResponse($e, 'Failed to retrieve postal server', 500);
        }
    }

    public function testConnection(PostalServer $postalServer): JsonResponse
    {
        try {
            $connectionTest = $this->postalServerService->testServerConnection($postalServer);

            return $this->successResponse(
                ['connection_successful' => $connectionTest],
                $connectionTest ? 'Connection test successful' : 'Connection test failed'
            );
        } catch (\Exception $e) {
            return $this->productionSafeErrorResponse($e, 'Failed to test connection', 500);
        }
    }
}
