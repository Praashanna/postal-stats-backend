<?php

namespace App\Http\Controllers;

use App\Models\PostalServer;
use App\Services\PostalServerService;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Resources\PostalServerResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

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
                new PostalServerResource($postalServer),
                'Postal server retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->productionSafeErrorResponse($e, 'Failed to retrieve postal server', 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate($this->postalServerService->getValidationRules());

            $server = $this->postalServerService->createServer($validated);

            $message = 'Postal server created successfully';
            if (!$server->is_active) {
                $message .= ' (connection test failed, server marked as inactive)';
            }

            return $this->successResponse(
                new PostalServerResource($server),
                $message
            );
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->productionSafeErrorResponse($e, 'Failed to create postal server', 500);
        }
    }

    public function update(Request $request, PostalServer $postalServer): JsonResponse
    {
        try {
            $validated = $request->validate($this->postalServerService->getValidationRules(true, $postalServer->id));

            $updatedServer = $this->postalServerService->updateServer($postalServer, $validated);

            return $this->successResponse(
                new PostalServerResource($updatedServer),
                'Postal server updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->productionSafeErrorResponse($e, 'Failed to update postal server', 500);
        }
    }

    public function destroy(PostalServer $postalServer): JsonResponse
    {
        try {
            $serverName = $postalServer->name;
            $this->postalServerService->deleteServer($postalServer);

            return $this->successResponse(
                null,
                "Postal server '{$serverName}' deleted successfully"
            );
        } catch (\Exception $e) {
            return $this->productionSafeErrorResponse($e, 'Failed to delete postal server', 500);
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

    public function toggleStatus(PostalServer $postalServer): JsonResponse
    {
        try {
            $updatedServer = $this->postalServerService->toggleServerStatus($postalServer);

            return $this->successResponse(
                new PostalServerResource($updatedServer),
                'Server status updated successfully'
            );
        } catch (\Exception $e) {
            return $this->productionSafeErrorResponse($e, 'Failed to update server status', 500);
        }
    }
}
