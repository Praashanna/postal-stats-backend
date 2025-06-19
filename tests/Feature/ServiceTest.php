<?php

use App\Services\AuthService;
use App\Services\UserService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('AuthService can create user', function () {
    $authService = app(AuthService::class);
    
    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
    ];

    $user = $authService->createUser($userData);

    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->password)->not->toBe('password123'); // Should be hashed
});

test('UserService can find user by email', function () {
    $user = User::factory()->create([
        'email' => 'findme@example.com',
    ]);

    $userService = app(UserService::class);
    $foundUser = $userService->findUserByEmail('findme@example.com');

    expect($foundUser)->toBeInstanceOf(User::class);
    expect($foundUser->id)->toBe($user->id);
});

test('UserService returns null for non-existent email', function () {
    $userService = app(UserService::class);
    $foundUser = $userService->findUserByEmail('nonexistent@example.com');

    expect($foundUser)->toBeNull();
});

test('UserService can get paginated users', function () {
    User::factory()->count(25)->create();

    $userService = app(UserService::class);
    $paginatedUsers = $userService->getPaginatedUsers(10);

    expect($paginatedUsers->count())->toBe(10);
    expect($paginatedUsers->total())->toBe(25);
});
