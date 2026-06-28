<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\PostalServer;
use App\Services\PostalService;
use Illuminate\Pagination\LengthAwarePaginator;

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

    public function test_can_list_bounces_by_error_type()
    {
        $this->actingAs($this->user, 'api');

        $server = PostalServer::factory()->create(['is_active' => true]);
        $paginator = new LengthAwarePaginator(
            collect([
                [
                    'error_type' => '550 5.1.1',
                    'bounce_count' => 12,
                    'unique_messages' => 10,
                    'last_delivery' => '2026-06-25 12:00:00',
                ],
            ]),
            1,
            15,
            1
        );

        app(PostalService::class)
            ->shouldReceive('getBouncesByErrorType')
            ->once()
            ->andReturn($paginator);

        $response = $this->getJson("/api/stats/server/{$server->id}/bounces/error-type");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.data.0.error_type', '550 5.1.1')
            ->assertJsonPath('data.data.0.bounce_count', 12)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => [
                            'error_type',
                            'bounce_count',
                            'unique_messages',
                            'last_delivery',
                        ],
                    ],
                    'pagination',
                ],
            ]);
    }

    public function test_can_export_bounce_addresses_by_error_type()
    {
        $this->actingAs($this->user, 'api');

        $server = PostalServer::factory()->create(['is_active' => true]);

        app(PostalService::class)
            ->shouldReceive('getBounceAddressesByErrorType')
            ->once()
            ->andReturn([
                (object) [
                    'address' => 'bad@example.com',
                    'from_address' => 'sender@example.com',
                    'subject' => 'Hello',
                    'status' => 'HardFail',
                    'error_type' => '550 5.1.1',
                    'delivery_code' => 550,
                    'delivery_output' => '550-5.1.1 User unknown',
                    'delivered_at' => '2026-06-25 12:00:00',
                ],
            ]);

        $response = $this->getJson("/api/export/server/{$server->id}/bounces/error-type?error_type=550%205.1.1");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee('Address,From Address,Subject,Status,Error Type,Delivery Code,Delivery Output,Delivered At', false)
            ->assertSee('bad@example.com', false)
            ->assertSee('550-5.1.1 User unknown', false);
    }

    public function test_can_suppress_bounce_addresses_by_error_type()
    {
        $this->actingAs($this->user, 'api');

        $server = PostalServer::factory()->create(['is_active' => true]);

        app(PostalService::class)
            ->shouldReceive('suppressBounceAddressesByErrorType')
            ->once()
            ->andReturn([
                'error_type' => '550 5.1.1',
                'duration' => '7d',
                'matched_addresses' => 2,
                'inserted' => 1,
                'updated' => 1,
                'suppressed' => 2,
                'keep_until' => '2026-07-02 12:00:00',
                'keep_until_timestamp' => 1782993600.0,
            ]);

        $response = $this->postJson("/api/stats/server/{$server->id}/bounces/error-type/suppressions", [
            'error_type' => '550-5.1.1',
            'duration' => '7d',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.error_type', '550 5.1.1')
            ->assertJsonPath('data.suppressed', 2);
    }
}
