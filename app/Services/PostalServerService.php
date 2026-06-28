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
        return PostalServer::query()
            ->with('organization')
            ->whereNull('deleted_at')
            ->orderBy('organization_id')
            ->orderBy('name')
            ->get();
    }

    public function getServerById(int $id): ?PostalServer
    {
        return PostalServer::with('organization')->find($id);
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

    public function getActiveServers(): Collection
    {
        return PostalServer::query()
            ->with('organization')
            ->whereNull('deleted_at')
            ->whereNull('suspended_at')
            ->orderBy('organization_id')
            ->orderBy('name')
            ->get();
    }
}
