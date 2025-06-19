<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthService
{
    /**
     * Create a new user account.
     *
     * @param array $userData
     * @return User
     */
    public function createUser(array $userData): User
    {
        return User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
        ]);
    }

    /**
     * Attempt to authenticate user and return JWT token.
     *
     * @param array $credentials
     * @return array
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                throw ValidationException::withMessages([
                    'email' => ['Invalid credentials provided.'],
                ]);
            }
        } catch (JWTException $e) {
            throw new \Exception('Could not create token');
        }

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => Auth::user()
        ];
    }

    /**
     * Logout user by invalidating the token.
     *
     * @return void
     */
    public function logout(): void
    {
        Auth::logout();
    }

    /**
     * Refresh the JWT token.
     *
     * @return array
     */
    public function refresh(): array
    {
        $token = Auth::refresh();

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => Auth::user()
        ];
    }

    /**
     * Get the authenticated user.
     *
     * @return User
     */
    public function getAuthenticatedUser(): User
    {
        return Auth::user();
    }
}
