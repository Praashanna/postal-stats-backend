<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\PostalServer;
use App\Services\PostalService;

class PostalServerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create();
        
        // Mock PostalService to avoid database connections during tests
        $this->mock(PostalService::class, function ($mock) {
            $mock->shouldReceive('testConnection')->andReturn(true);
            $mock->shouldReceive('setupConnection')->andReturn(null);
        });
    }

    public function test_can_list_postal_servers()
    {
        $this->actingAs($this->user, 'api');
        
        // Create test servers
        PostalServer::factory()->count(3)->create();

        $response = $this->getJson('/api/servers');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'host',
                            'port',
                            'database',
                            'username',
                            'is_active'
                        ]
                    ]
                ]);
    }

    public function test_can_create_postal_server()
    {
        $this->actingAs($this->user, 'api');

        $serverData = [
            'name' => 'Test Server',
            'host' => 'localhost',
            'port' => '3306',
            'database' => 'postal_test',
            'username' => 'test_user',
            'password' => 'test_password',
            'is_active' => true
        ];

        $response = $this->postJson('/api/servers', $serverData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'id',
                        'name',
                        'host',
                        'port',
                        'database',
                        'username',
                        'is_active'
                    ]
                ]);

        $this->assertDatabaseHas('postal_servers', [
            'name' => 'Test Server',
            'host' => 'localhost',
            'database' => 'postal_test'
        ]);
    }

    public function test_cannot_create_server_with_invalid_data()
    {
        $this->actingAs($this->user, 'api');
        
        $response = $this->postJson('/api/servers', [
            'name' => '', // Required field missing
            'host' => 'localhost'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'database', 'username', 'password']);
    }

    public function test_can_show_postal_server()
    {
        $this->actingAs($this->user, 'api');
        
        $server = PostalServer::factory()->create();

        $response = $this->getJson("/api/servers/{$server->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'id',
                        'name',
                        'host',
                        'port',
                        'database',
                        'username',
                        'is_active'
                    ]
                ]);
    }

    public function test_requires_authentication_for_all_endpoints()
    {
        $endpoints = [
            ['GET', '/api/servers'],
            ['POST', '/api/servers', ['name' => 'test']],
            ['GET', '/api/servers/1'],
            ['PUT', '/api/servers/1', ['name' => 'test']],
            ['DELETE', '/api/servers/1'],
        ];

        foreach ($endpoints as $endpoint) {
            $method = $endpoint[0];
            $url = $endpoint[1];
            $data = $endpoint[2] ?? [];
            
            $response = $this->json($method, $url, $data);
            $response->assertStatus(401);
        }
    }
}
