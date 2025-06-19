<?php

namespace App\Services;

use App\Models\PostalServer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;

class PostalService
{
    /**
     * Set up dynamic database connection for a postal server
     */
    public function setupConnection(PostalServer $server): void
    {
        $connectionName = $server->getDynamicConnectionName();
        $config = $server->getConnectionConfig();
        
        Config::set("database.connections.{$connectionName}", $config);
        
        // Test the connection
        try {
            DB::connection($connectionName)->getPdo();
        } catch (\Exception $e) {
            Log::error("Failed to connect to postal server '{$server->name}': " . $e->getMessage(), [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'host' => $server->host,
                'database' => $server->database,
                'exception' => $e
            ]);
            throw new \Exception("Failed to connect to postal server '{$server->name}'");
        }
    }

    public function getTimestamp(string $period) {
        $endDate = Carbon::now();
        $startDate = match ($period) {
            '1d' => $endDate->copy()->subDay(),
            '7d' => $endDate->copy()->subDays(7),
            '14d' => $endDate->copy()->subDays(14),
            '30d' => $endDate->copy()->subDays(30),
            'today' => $endDate->copy()->startOfDay(),
            'yesterday' => $endDate->copy()->subDay()->startOfDay(),
            default => $endDate->copy()->subDays(30),
        };

        if ($period === 'yesterday') {
            $endDate = $endDate->copy()->subDay()->endOfDay();
        }

        return [$startDate->startOfDay(), $endDate];
    }

    /**
     * Get email statistics for a postal server (updated with opens)
     */
    public function getServerStats(PostalServer $server, array $filters = []): array
    {
        $this->setupConnection($server);
        $connection = $server->getDynamicConnectionName();
        
        // Date filtering - convert to timestamp format used by Postal
        $period = $filters['period'] ?? '7d';
        [$startDate, $endDate] = $this->getTimestamp($period);

        $startTimestamp = $startDate->timestamp;
        $endTimestamp = $endDate->timestamp;

        $stats = DB::connection($connection)
            ->table('messages')
            ->whereBetween('timestamp', [$startTimestamp, $endTimestamp])
            ->selectRaw('
                COUNT(*) as total_sent,
                SUM(CASE WHEN status = "Sent" THEN 1 ELSE 0 END) as total_delivered,
                SUM(CASE WHEN status IN ("HardFail", "Bounced") THEN 1 ELSE 0 END) as total_bounced,
                SUM(CASE WHEN held = 1 THEN 1 ELSE 0 END) as total_held,
                SUM(CASE WHEN loaded IS NOT NULL THEN 1 ELSE 0 END) as total_opened
            ')
            ->first();

        $deliveryRate = $stats->total_sent > 0 ? round(($stats->total_delivered / $stats->total_sent) * 100, 2) : 0;
        $bounceRate = $stats->total_sent > 0 ? round(($stats->total_bounced / $stats->total_sent) * 100, 2) : 0;
        $openRate = $stats->total_delivered > 0 ? round(($stats->total_opened / $stats->total_delivered) * 100, 2) : 0;

        if (in_array($period, ['today', 'yesterday'])) {
            $chartStats = DB::connection($connection)
                ->table('messages')
                ->selectRaw("
                    HOUR(FROM_UNIXTIME(timestamp)) as hour,
                    DATE(FROM_UNIXTIME(timestamp)) as date,
                    COUNT(*) as sent,
                    SUM(CASE WHEN status = 'Sent' THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN status IN ('HardFail', 'Bounced') THEN 1 ELSE 0 END) as bounced,
                    SUM(CASE WHEN held = 1 THEN 1 ELSE 0 END) as held,
                    SUM(CASE WHEN loaded IS NOT NULL THEN 1 ELSE 0 END) as opens
                ")
                ->whereBetween('timestamp', [$startTimestamp, $endTimestamp])
                ->groupBy(DB::raw('DATE(FROM_UNIXTIME(timestamp)), HOUR(FROM_UNIXTIME(timestamp))'))
                ->orderBy('date')
                ->orderBy('hour')
                ->get()
                ->keyBy(function ($item) {
                    return $item->date . '_' . $item->hour;
                });


            $chartData = [];
            $currentDate = $startDate->copy();
            
            while ($currentDate <= $endDate) {
                for ($hour = 0; $hour < 24; $hour++) {
                    $dateStr = $currentDate->format('Y-m-d');
                    $key = $dateStr . '_' . $hour;
                    
                    // Skip future hours for today
                    if ($period === 'today' && $currentDate->copy()->setHour($hour) > Carbon::now()) {
                        break;
                    }
                    
                    $existing = $chartStats->get($key);
                    
                    $chartData[] = [
                        'date' => $dateStr . ' ' . sprintf('%02d:00', $hour),
                        'hour' => $hour,
                        'sent' => $existing ? (int) $existing->sent : 0,
                        'delivered' => $existing ? (int) $existing->delivered : 0,
                        'bounced' => $existing ? (int) $existing->bounced : 0,
                        'held' => $existing ? (int) $existing->held : 0,
                        'opens' => $existing ? (int) $existing->opens : 0
                    ];
                }
                $currentDate->addDay();
            }
        } else {
            // Daily data for other periods
            $dailyStats = DB::connection($connection)
                ->table('messages')
                ->selectRaw("
                    DATE(FROM_UNIXTIME(timestamp)) as date,
                    COUNT(*) as sent,
                    SUM(CASE WHEN status = 'Sent' THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN status IN ('HardFail', 'Bounced') THEN 1 ELSE 0 END) as bounced,
                    SUM(CASE WHEN held = 1 THEN 1 ELSE 0 END) as held,
                    SUM(CASE WHEN loaded IS NOT NULL THEN 1 ELSE 0 END) as opens
                ")
                ->whereBetween('timestamp', [$startTimestamp, $endTimestamp])
                ->groupBy(DB::raw('DATE(FROM_UNIXTIME(timestamp))'))
                ->orderBy('date')
                ->get()
                ->keyBy('date');

            // Generate complete daily series
            $chartData = [];
            $currentDate = $startDate->copy();
            
            while ($currentDate <= $endDate) {
                $dateStr = $currentDate->format('Y-m-d');
                $existing = $dailyStats->get($dateStr);
                
                $chartData[] = [
                    'date' => $dateStr,
                    'sent' => $existing ? (int) $existing->sent : 0,
                    'delivered' => $existing ? (int) $existing->delivered : 0,
                    'bounced' => $existing ? (int) $existing->bounced : 0,
                    'held' => $existing ? (int) $existing->held : 0,
                    'opens' => $existing ? (int) $existing->opens : 0
                ];
                
                $currentDate->addDay();
            }
        }

        return [
            'data' => [
                'totalSent' => $stats->total_sent,
                'totalDelivered' => $stats->total_delivered,
                'totalBounced' => $stats->total_bounced,
                'totalHeld' => $stats->total_held,
                'totalOpened' => $stats->total_opened,
                'deliveryRate' => $deliveryRate,
                'bounceRate' => $bounceRate,
                'openRate' => $openRate,
                'chartData' => $chartData
            ],
            'message' => 'Server statistics retrieved successfully',
            'status' => 'success'
        ];
    }

    /**
     * Get bounce data summary statistics
     */
    public function getBounceData(PostalServer $server, array $filters = []): array
    {
        $this->setupConnection($server);
        $connection = $server->getDynamicConnectionName();
        
        // Date filtering
        [$startDate, $endDate] = $this->getTimestamp($filters['period'] ?? '30d');

        $stats = DB::connection($connection)
            ->table('messages')
            ->whereBetween('timestamp', [$startDate->timestamp, $endDate->timestamp])
            ->selectRaw('
                COUNT(*) as total_sent,
                SUM(CASE WHEN status IN ("HardFail", "Bounced") THEN 1 ELSE 0 END) as total_bounced,
                COUNT(DISTINCT(CASE WHEN status IN ("HardFail", "Bounced") THEN SUBSTRING_INDEX(rcpt_to, "@", -1) END)) as total_domains
            ')
            ->first();

        $bounceRate = $stats->total_sent > 0 ? round(($stats->total_bounced / $stats->total_sent) * 100, 2) : 0;

        $topDomains = DB::connection($connection)
            ->table('messages')
            ->selectRaw("
                SUBSTRING_INDEX(rcpt_to, '@', -1) as domain,
                COUNT(*) as count
            ")
            ->where('status', ['HardFail', 'Bounced'])
            ->whereBetween('timestamp', [$startDate->timestamp, $endDate->timestamp])
            ->groupBy(DB::raw("SUBSTRING_INDEX(rcpt_to, '@', -1)"))
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) use ($stats) {
                return [
                    'domain' => $item->domain,
                    'count' => $item->count,
                    'percentage' => $stats->total_bounced > 0 ? round(($item->count / $stats->total_bounced) * 100, 2) : 0
                ];
            })->toArray();

        return [
            'totalBounced' => $stats->total_bounced,
            'totalDomains' => $stats->total_domains,
            'bounceRate' => $bounceRate,
            'topDomains' => $topDomains,
        ];
    }

    /**
     * Get bounce statistics by domain
     */
    public function getBouncesByDomain(PostalServer $server, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $this->setupConnection($server);
        $connection = $server->getDynamicConnectionName();
        
        [$startDate, $endDate] = $this->getTimestamp($filters['period'] ?? '30d');


        $stats = DB::connection($connection)
            ->table('messages')
            ->whereBetween('timestamp', [$startDate->timestamp, $endDate->timestamp])
            ->selectRaw('
                SUM(CASE WHEN status IN ("HardFail", "Bounced") THEN 1 ELSE 0 END) as total_bounced
            ')
            ->first();

        $query = DB::connection($connection)
            ->table('messages')
            ->selectRaw("
                SUBSTRING_INDEX(rcpt_to, '@', -1) as domain,
                COUNT(*) as bounce_count,
                COUNT(DISTINCT rcpt_to) as unique_addresses
            ")
            ->where('status', ['HardFail', 'Bounced'])
            ->whereRaw('SUBSTRING_INDEX(rcpt_to, "@", -1) LIKE ?', ['%' . ($filters['q'] ?? '') . '%'])
            ->whereBetween('timestamp', [$startDate->timestamp, $endDate->timestamp])
            ->groupBy(DB::raw("SUBSTRING_INDEX(rcpt_to, '@', -1)"))
            ->orderBy('bounce_count', 'desc');

        // Get current page
        $currentPage = Paginator::resolveCurrentPage();
        $total = $query->get()->count();
        
        // Get paginated results
        $results = $query->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get();

        return new LengthAwarePaginator(
            [
                'domains' => $results,
                'totalBounces' => $stats->total_bounced
            ],
            $total,
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    public function getBouncesByAddress(PostalServer $server, array $filters) {
        $this->setupConnection($server);
        $connection = $server->getDynamicConnectionName();

        [$startDate, $endDate] = $this->getTimestamp($filters['period'] ?? '30d');
        $perPage = $filters['perPage'] ?? 100;

        $query = DB::connection($connection)
            ->table('messages')
            ->selectRaw("
                rcpt_to as address,
                SUBSTRING_INDEX(rcpt_to, '@', -1) as domain,
                COUNT(*) as bounce_count,
                MAX(timestamp) as last_bounce
            ")
            ->where('rcpt_to', 'like', '%' . ($filters['q'] ?? '') . '%')
            ->whereIn('status', ['HardFail', 'Bounced'])
            ->whereBetween('timestamp', [$startDate->timestamp, $endDate->timestamp])
            ->groupBy('rcpt_to')
            ->orderByRaw('COUNT(*) desc');

        // Apply domain filter if provided
        if (!empty($filters['domain'])) {
            $query->where('rcpt_to', 'LIKE', '%@' . $filters['domain']);
        }

        // Get current page
        $currentPage = Paginator::resolveCurrentPage();
        $total = $query->get()->count();

        // Get paginated results
        $results = $query->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(function ($item) {
                return [
                    'address' => $item->address,
                    'domain' => $item->domain,
                    'bounce_count' => $item->bounce_count,
                    'last_bounce' => Carbon::createFromTimestamp($item->last_bounce)->toDateTimeString()
                ];
            });

        return new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Export bounce data to CSV
     */
    public function exportBounceData(PostalServer $server, array $filters = []): array
    {
        $this->setupConnection($server);
        $connection = $server->getDynamicConnectionName();
        
        [$startDate, $endDate] = $this->getTimestamp($filters['period'] ?? '30d');

        $query = DB::connection($connection)
            ->table('messages')
            ->select([
                'rcpt_to as to_address',
                'mail_from as from_address',
                'subject',
                'status',
                DB::raw('FROM_UNIXTIME(timestamp) as sent_at')
            ])
            ->where('status', ['HardFail', 'Bounced'])
            ->whereBetween('timestamp', [$startDate->timestamp, $endDate->timestamp]);

        // Apply additional filters
        if (!empty($filters['domain'])) {
            $query->where('rcpt_to', 'LIKE', '%@' . $filters['domain']);
        }

        $query->orderBy('timestamp', 'desc');

        return $query->get()->toArray();
    }

    /**
     * Test connection to a postal server
     */
    public function testConnection(PostalServer $server): bool
    {
        try {
            // If server doesn't have an ID yet, create a temporary connection
            if (!$server->id) {
                return $this->testTemporaryConnection($server);
            }
            
            $this->setupConnection($server);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Test connection with temporary configuration (for unsaved servers)
     */
    public function testTemporaryConnection(PostalServer $server): bool
    {
        try {
            $tempConnectionName = 'postal_temp_' . uniqid();
            $config = $server->getConnectionConfig();
            
            Config::set("database.connections.{$tempConnectionName}", $config);
            
            // Test the connection
            DB::connection($tempConnectionName)->getPdo();
            
            // Clean up the temporary connection
            Config::forget("database.connections.{$tempConnectionName}");
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
