<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PostalServer;
use App\Services\PostalService;

class AddPostalServer extends Command
{
    protected $signature = 'postal:add-server 
                            {--name= : The name of the postal server}
                            {--host= : The database host}
                            {--port=3306 : The database port}
                            {--database= : The database name}
                            {--username= : The database username}
                            {--password= : The database password}
                            {--api-key= : The API key (optional)}
                            {--api-url= : The API URL (optional)}
                            {--active=true : Whether the server is active}';

    protected $description = 'Add a new postal server configuration';

    protected PostalService $postalService;

    public function __construct(PostalService $postalService)
    {
        parent::__construct();
        $this->postalService = $postalService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Adding a new postal server...');

        // Get server details from options or prompt
        $name = $this->option('name') ?: $this->ask('Server name');
        $host = $this->option('host') ?: $this->ask('Database host');
        $port = $this->option('port') ?: $this->ask('Database port', '3306');
        $database = $this->option('database') ?: $this->ask('Database name');
        $username = $this->option('username') ?: $this->ask('Database username');
        $password = $this->option('password') ?: $this->secret('Database password');
        $isActive = $this->option('active') === 'true' || $this->option('active') === true;

        // Validate required fields (password can be empty for some database configurations)
        if (empty($name) || empty($host) || empty($database) || empty($username)) {
            $this->error('Name, host, database, and username are required!');
            return 1;
        }

        // Check if server name already exists
        if (PostalServer::where('name', $name)->exists()) {
            $this->error("A server with the name '{$name}' already exists!");
            return 1;
        }

        try {
            // Create the server
            $server = PostalServer::create([
                'name' => $name,
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'username' => $username,
                'password' => $password ?: null, // Convert empty string to null
                'is_active' => $isActive,
            ]);

            $this->info("Testing connection to postal server...");

            // Test the connection
            if ($this->postalService->testConnection($server)) {
                $this->info("✅ Postal server '{$name}' added successfully!");
                $this->info("Server ID: {$server->id}");
                $this->info("Connection test: Passed");
                return 0;
            } else {
                $this->warn("⚠️  Postal server '{$name}' was added but connection test failed.");
                $this->warn("Please verify the database credentials and ensure the server is accessible.");
                return 0;
            }
        } catch (\Exception $e) {
            $this->error("Failed to add postal server: " . $e->getMessage());
            return 1;
        }
    }
}
