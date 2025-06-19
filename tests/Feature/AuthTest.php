<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'access_token',
            'token_type',
            'expires_in',
            'user' => [
                'id',
                'name',
                'email',
                'created_at',
                'updated_at',
            ],
        ])
        ->assertJson([
            'status' => 'success',
            'token_type' => 'bearer',
        ]);
});

test('user cannot login with invalid credentials', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'invalid@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'status' => 'error',
        ]);
});

test('login requires valid email and password', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'not-an-email',
        'password' => '',
    ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'status',
            'message',
            'errors',
        ]);
});
