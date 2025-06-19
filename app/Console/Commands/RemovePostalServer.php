<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PostalServer;

class RemovePostalServer extends Command
{
    protected $signature = 'postal:remove-server 
                            {--id= : The ID of the postal server to remove}
                            {--name= : The name of the postal server to remove}
                            {--force : Force removal without confirmation}';

    protected $description = 'Remove a postal server configuration';

    public function handle()
    {
        $this->info('Removing postal server...');

        // Get server by ID or name
        $serverId = $this->option('id');
        $serverName = $this->option('name');
        $force = $this->option('force');

        if (!$serverId && !$serverName) {
            // Show list of servers to choose from
            $servers = PostalServer::all();
            
            if ($servers->isEmpty()) {
                $this->error('No postal servers found.');
                return 1;
            }

            $this->info('Available postal servers:');
            $this->table(
                ['ID', 'Name', 'Host', 'Database', 'Status'],
                $servers->map(function ($server) {
                    return [
                        $server->id,
                        $server->name,
                        $server->host,
                        $server->database,
                        $server->is_active ? 'Active' : 'Inactive'
                    ];
                })
            );

            $serverId = $this->ask('Enter the ID of the server to remove');
        }

        // Find the server
        $server = null;
        if ($serverId) {
            $server = PostalServer::find($serverId);
        } elseif ($serverName) {
            $server = PostalServer::where('name', $serverName)->first();
        }

        if (!$server) {
            $this->error('Postal server not found.');
            return 1;
        }

        // Show server details
        $this->info("Server to be removed:");
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $server->id],
                ['Name', $server->name],
                ['Host', $server->host],
                ['Port', $server->port],
                ['Database', $server->database],
                ['Username', $server->username],
                ['Status', $server->is_active ? 'Active' : 'Inactive'],
                ['Created', $server->created_at->format('Y-m-d H:i:s')],
            ]
        );

        // Confirmation
        if (!$force) {
            $confirmed = $this->confirm(
                "Are you sure you want to remove the postal server '{$server->name}'? This action cannot be undone."
            );

            if (!$confirmed) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        try {
            // Remove the server
            $serverName = $server->name;
            $server->delete();

            $this->info("✅ Postal server '{$serverName}' has been removed successfully!");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to remove postal server: " . $e->getMessage());
            return 1;
        }
    }
}
