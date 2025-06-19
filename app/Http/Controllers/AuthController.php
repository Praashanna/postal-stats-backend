<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Resources\UserResource;
use App\Services\AuthService;

class AuthController extends Controller
{
    use ApiResponseTrait;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $result = $this->authService->login($request->only('email', 'password'));
            
            return response()->json([
                'status' => 'success',
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'],
                'expires_in' => $result['expires_in'],
                'user' => new UserResource($result['user'])
            ]);
        } catch (\Exception $e) {
            return $this->productionSafeErrorResponse($e, 'Authentication failed', 401);
        }
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'user' => new UserResource($this->authService->getAuthenticatedUser())
        ]);
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out'
        ]);
    }

    public function refresh(): JsonResponse
    {
        try {
            $result = $this->authService->refresh();
            
            return response()->json([
                'status' => 'success',
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'],
                'expires_in' => $result['expires_in'],
                'user' => new UserResource($result['user'])
            ]);
        } catch (\Exception $e) {
            return $this->productionSafeErrorResponse($e, 'Token refresh failed', 401);
        }
    }
}
