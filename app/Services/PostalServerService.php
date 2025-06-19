<?php

namespace App\Services;

use App\Models\PostalServer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class PostalServerService
{
    protected PostalService $postalService;

    public function __construct(PostalService $postalService)
    {
        $this->postalService = $postalService;
    }

    public function getAllServers(): Collection
    {
        return PostalServer::all();
    }

    public function getServerById(int $id): ?PostalServer
    {
        return PostalServer::find($id);
    }

    public function createServer(array $data): PostalServer
    {
        $server = PostalServer::create([
            'name' => $data['name'],
            'host' => $data['host'],
            'port' => $data['port'],
            'database' => $data['database'],
            'username' => $data['username'],
            'password' => $data['password'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $connectionTest = $this->testServerConnection($server);
        if (!$connectionTest) {
            $server->update(['is_active' => false]);
            Log::warning("Server '{$server->name}' created but connection test failed. Marked as inactive.", [
                'server_id' => $server->id,
                'host' => $server->host,
                'database' => $server->database
            ]);
        }

        return $server->fresh();
    }

    public function updateServer(PostalServer $server, array $data): PostalServer
    {
        $connectionFields = ['host', 'port', 'database', 'username', 'password'];
        $connectionDetailsUpdated = array_intersect_key($data, array_flip($connectionFields));

        $server->update($data);

        if (!empty($connectionDetailsUpdated)) {
            $connectionTest = $this->testServerConnection($server);
            if (!$connectionTest && $server->is_active) {
                $server->update(['is_active' => false]);
                Log::warning("Server '{$server->name}' connection test failed after update. Marked as inactive.", [
                    'server_id' => $server->id,
                    'updated_fields' => array_keys($connectionDetailsUpdated)
                ]);
            }
        }

        return $server->fresh();
    }

    public function deleteServer(PostalServer $server): bool
    {
        $serverName = $server->name;
        $serverId = $server->id;
        
        $deleted = $server->delete();
        
        if ($deleted) {
            Log::info("Postal server deleted successfully", [
                'server_id' => $serverId,
                'server_name' => $serverName
            ]);
        }

        return $deleted;
    }

    public function testServerConnection(PostalServer $server): bool
    {
        try {
            return $this->postalService->testConnection($server);
        } catch (\Exception $e) {
            Log::error("Connection test failed for server '{$server->name}': " . $e->getMessage(), [
                'server_id' => $server->id,
                'host' => $server->host,
                'database' => $server->database,
                'exception' => $e
            ]);
            return false;
        }
    }

    public function getValidationRules(bool $isUpdate = false, ?int $serverId = null): array
    {
        $rules = [
            'name' => 'required|string|max:255|unique:postal_servers,name',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'database' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ];

        if ($isUpdate) {
            $rules['name'] = $serverId 
                ? 'sometimes|required|string|max:255|unique:postal_servers,name,' . $serverId
                : 'sometimes|required|string|max:255|unique:postal_servers,name';
            
            $rules['host'] = 'sometimes|required|string|max:255';
            $rules['port'] = 'sometimes|required|integer|min:1|max:65535';
            $rules['database'] = 'sometimes|required|string|max:255';
            $rules['username'] = 'sometimes|required|string|max:255';
        }

        return $rules;
    }

    public function getActiveServers(): Collection
    {
        return PostalServer::where('is_active', true)->get();
    }

    public function toggleServerStatus(PostalServer $server): PostalServer
    {
        $newStatus = !$server->is_active;
        
        if ($newStatus) {
            $connectionTest = $this->testServerConnection($server);
            if (!$connectionTest) {
                throw new \Exception("Cannot activate server: connection test failed");
            }
        }

        $server->update(['is_active' => $newStatus]);
        
        Log::info("Server status toggled", [
            'server_id' => $server->id,
            'server_name' => $server->name,
            'new_status' => $newStatus ? 'active' : 'inactive'
        ]);

        return $server->fresh();
    }
}
