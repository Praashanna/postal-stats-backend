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
    protected const SUPPRESSION_PRESETS = [
        'google' => [
            'google.com', 'google.ad', 'google.ae', 'google.com.af', 'google.com.ag', 'google.al',
            'google.am', 'google.co.ao', 'google.com.ar', 'google.as', 'google.at', 'google.com.au',
            'google.az', 'google.ba', 'google.com.bd', 'google.be', 'google.bf', 'google.bg',
            'google.com.bh', 'google.bi', 'google.bj', 'google.com.bn', 'google.com.bo',
            'google.com.br', 'google.bs', 'google.bt', 'google.co.bw', 'google.by', 'google.com.bz',
            'google.ca', 'google.cd', 'google.cf', 'google.cg', 'google.ch', 'google.ci',
            'google.co.ck', 'google.cl', 'google.cm', 'google.cn', 'google.com.co', 'google.co.cr',
            'google.com.cu', 'google.cv', 'google.com.cy', 'google.cz', 'google.de', 'google.dj',
            'google.dk', 'google.dm', 'google.com.do', 'google.dz', 'google.com.ec', 'google.ee',
            'google.com.eg', 'google.es', 'google.com.et', 'google.fi', 'google.com.fj', 'google.fm',
            'google.fr', 'google.ga', 'google.ge', 'google.gg', 'google.com.gh', 'google.com.gi',
            'google.gl', 'google.gm', 'google.gr', 'google.com.gt', 'google.gy', 'google.com.hk',
            'google.hn', 'google.hr', 'google.ht', 'google.hu', 'google.co.id', 'google.ie',
            'google.co.il', 'google.im', 'google.co.in', 'google.iq', 'google.is', 'google.it',
            'google.je', 'google.com.jm', 'google.jo', 'google.co.jp', 'google.co.ke', 'google.com.kh',
            'google.ki', 'google.kg', 'google.co.kr', 'google.com.kw', 'google.kz', 'google.la',
            'google.com.lb', 'google.li', 'google.lk', 'google.co.ls', 'google.lt', 'google.lu',
            'google.lv', 'google.com.ly', 'google.co.ma', 'google.md', 'google.me', 'google.mg',
            'google.mk', 'google.ml', 'google.com.mm', 'google.mn', 'google.com.mt', 'google.mu',
            'google.mv', 'google.mw', 'google.com.mx', 'google.com.my', 'google.co.mz',
            'google.com.na', 'google.com.ng', 'google.com.ni', 'google.ne', 'google.nl', 'google.no',
            'google.com.np', 'google.nr', 'google.nu', 'google.co.nz', 'google.com.om', 'google.com.pa',
            'google.com.pe', 'google.com.pg', 'google.com.ph', 'google.com.pk', 'google.pl',
            'google.pn', 'google.com.pr', 'google.ps', 'google.pt', 'google.com.py', 'google.com.qa',
            'google.ro', 'google.ru', 'google.rw', 'google.com.sa', 'google.com.sb', 'google.sc',
            'google.se', 'google.com.sg', 'google.sh', 'google.si', 'google.sk', 'google.com.sl',
            'google.sn', 'google.so', 'google.sm', 'google.sr', 'google.st', 'google.com.sv',
            'google.td', 'google.tg', 'google.co.th', 'google.com.tj', 'google.tl', 'google.tm',
            'google.tn', 'google.to', 'google.com.tr', 'google.tt', 'google.com.tw', 'google.co.tz',
            'google.com.ua', 'google.co.ug', 'google.co.uk', 'google.com.uy', 'google.co.uz',
            'google.com.vc', 'google.co.ve', 'google.co.vi', 'google.com.vn', 'google.vu',
            'google.ws', 'google.rs', 'google.co.za', 'google.co.zm', 'google.co.zw', 'google.cat',
            'gmail.com', 'googlemail.com', 'googleusercontent.com'
        ],
        'microsoft' => [
            'outlook.com', 'passport.com', 'hotmail.ac', 'hotmail.bb', 'hotmail.bs', 'hotmail.cl',
            'hotmail.co.ve', 'hotmail.com.ar', 'hotmail.com.bo', 'hotmail.com.br', 'hotmail.com.do',
            'hotmail.com.tt', 'hotmail.com.ve', 'live.ca', 'live.cl', 'live.com.ar', 'live.com.co',
            'live.com.mx', 'live.com.pe', 'live.com.ve', 'outlook.bz', 'outlook.cl', 'outlook.co',
            'outlook.co.cr', 'outlook.com.ar', 'outlook.com.br', 'outlook.com.pe', 'outlook.com.py',
            'outlook.ec', 'outlook.hn', 'outlook.ht', 'outlook.mx', 'outlook.pa', 'outlook.uy',
            'webtv.net', 'windowslive.com', 'msn.com', 'msn.nl', 'live.com', 'hotmail.ca',
            'hotmail.com', 'hotmail.at', 'hotmail.ba', 'hotmail.be', 'hotmail.ch', 'hotmail.co.at',
            'hotmail.co.il', 'hotmail.co.ug', 'hotmail.co.uk', 'hotmail.co.za', 'hotmail.com.ly',
            'hotmail.com.pl', 'hotmail.com.ru', 'hotmail.com.tr', 'hotmail.de', 'hotmail.dk',
            'hotmail.ee', 'hotmail.es', 'hotmail.fi', 'hotmail.fr', 'hotmail.gr', 'hotmail.hu',
            'hotmail.ie', 'hotmail.it', 'hotmail.lt', 'hotmail.lu', 'hotmail.lv', 'hotmail.ly',
            'hotmail.mw', 'hotmail.no', 'hotmail.pt', 'hotmail.rs', 'hotmail.se', 'hotmail.sh',
            'hotmail.sk', 'hotmail.ua', 'live.at', 'live.be', 'live.ch', 'live.co.uk', 'live.co.za',
            'live.com.pt', 'live.de', 'live.dk', 'live.fi', 'live.fr', 'live.ie', 'live.it',
            'live.nl', 'live.no', 'live.ru', 'live.se', 'outlook.at', 'outlook.be', 'outlook.bg',
            'outlook.cm', 'outlook.co.il', 'outlook.com.es', 'outlook.com.gr', 'outlook.com.hr',
            'outlook.com.tr', 'outlook.com.ua', 'outlook.cz', 'outlook.de', 'outlook.dk',
            'outlook.es', 'outlook.fr', 'outlook.hu', 'outlook.ie', 'outlook.it', 'outlook.lv',
            'outlook.pt', 'outlook.ro', 'outlook.si', 'outlook.sk', 'windowslive.es', 'hotmail.as',
            'hotmail.co.id', 'hotmail.co.in', 'hotmail.co.jp', 'hotmail.co.kr', 'hotmail.co.nz',
            'hotmail.co.pn', 'hotmail.co.th', 'hotmail.com.au', 'hotmail.com.hk', 'hotmail.com.my',
            'hotmail.com.ph', 'hotmail.com.sg', 'hotmail.com.tw', 'hotmail.com.uz', 'hotmail.com.vn',
            'hotmail.hk', 'hotmail.jp', 'hotmail.la', 'hotmail.mn', 'hotmail.my', 'hotmail.net.fj',
            'hotmail.ph', 'hotmail.pn', 'hotmail.sg', 'hotmail.vu', 'live.cn', 'live.co.in',
            'live.co.kr', 'live.com.au', 'live.com.my', 'live.com.ph', 'live.com.pk',
            'live.com.sg', 'live.hk', 'live.in', 'live.jp', 'live.ph', 'outlook.co.id',
            'outlook.co.nz', 'outlook.co.th', 'outlook.com.au', 'outlook.com.vn', 'outlook.in',
            'outlook.jp', 'outlook.kr', 'outlook.la', 'outlook.my', 'outlook.ph', 'outlook.pk',
            'outlook.sa', 'outlook.sg', 'affirmednetworks.com', 'ally.io', 'appcenter.ms',
            'botkit.ai', 'citusdata.com', 'clipchamp.com', 'cloudknox.io', 'communitysift.com',
            'cyberx-labs.com', 'flipgrid.com', 'fundingspot.com', 'fungible.com', 'hexadite.com',
            'howdy.ai', 'initiativegaming.com', 'jclarity.com', 'kinvolk.io', 'lobe.ai', 'm12.vc',
            'microsoft.com', 'mijnafvalwijzer.nl', 'minit.io', 'mojang.com', 'mover.io',
            'movere.io', 'peer5.com', 'playfab.com', 'promoteiq.com', 'riskiq.com', 'riskiq.net',
            'seaofthieves.com', 'semanticmachines.com', 'smash.gg', 'softomotive.com',
            'spotfront.com', 'start.gg', 'winautomation.com', 'xandr.com', 'xoxco.com', 'bing.it',
            'bing.nl', 'microsoft.it', 'microsoft.nl', 'msft.it', 'msn.fi', 'msn.it', 'office.de',
            'windowsetvous.fr'
        ],
        'yahoo' => [
            'yahoo.com', 'rocketmail.com', 'ymail.com', 'yahoo.com.ar', 'y7mail.com', 'yahoo.com.au',
            'yahoo.bg', 'yahoo.com.br', 'yahoo.ca', 'yahoo.cl', 'yahoo.com.cn', 'yahoo.cn',
            'yahoo.com.co', 'yahoo.de', 'yahoo.dk', 'yahoo.es', 'yahoo.fr', 'yahoo.gr',
            'yahoo.com.hk', 'yahoo.co.id', 'yahoo.ie', 'yahoo.in', 'yahoo.co.in', 'yahoo.it',
            'yahoo.co.kr', 'yahoo.lt', 'yahoo.lv', 'yahoo.com.mx', 'yahoo.com.my', 'yahoo.no',
            'yahoo.co.nz', 'yahoo.com.pe', 'yahoo.com.ph', 'yahoo.com.pk', 'yahoo.pl', 'yahoo.ro',
            'yahoo.se', 'yahoo.com.sg', 'yahoo.co.th', 'yahoo.com.tr', 'yahoo.com.tw',
            'yahoo.ua', 'yahoo.co.uk', 'yahoo.com.ve', 'yahoo.com.vn', 'bellsouth.net',
            'ameritech.net', 'att.net', 'attworld.com', 'flash.net', 'nvbell.net', 'pacbell.net',
            'prodigy.net', 'sbcglobal.net', 'snet.net', 'swbell.net', 'wans.net', 'btinternet.com',
            'btopenworld.com', 'talk21.com', 'rogers.com', 'nl.rogers.com', 'demobroadband.com',
            'xtra.co.nz', 'verizon.net',
            'aim.com', 'aol.at', 'aol.be', 'aol.ch', 'aol.cl', 'aol.co.nz', 'aol.co.uk', 'aol.com',
            'aol.com.ar', 'aol.com.au', 'aol.com.br', 'aol.com.co', 'aol.com.mx', 'aol.com.tr',
            'aol.com.ve', 'aol.cz', 'aol.de', 'aol.dk', 'aol.es', 'aol.fi', 'aol.fr', 'aol.hk',
            'aol.in', 'aol.it', 'aol.jp', 'aol.kr', 'aol.nl', 'aol.pl', 'aol.ru', 'aol.se',
            'aol.tw', 'aolchina.com', 'aolnews.com', 'aolvideo.com', 'aprilshowersflorists.com',
            'asylum.com', 'bellatlantic.net', 'bloomoffaribault.com', 'citlink.net', 'compuserve.com',
            'cox.net', 'cs.com', 'csi.com', 'dogsinthenews.com', 'epix.net', 'frontier.com',
            'frontiernet.net', 'geocities.com', 'goowy.com', 'gte.net', 'kimo.com', 'lemondrop.com',
            'mcom.com', 'myfrontiermail.com', 'myyahoo.com', 'netbusiness.com', 'netscape.com',
            'netscape.net', 'newnorth.net', 'robertgillingsproductions.com', 'safesocial.com',
            'simivalleyflowers.com', 'sky.com', 'spinner.com', 'switched.com', 'urlesque.com',
            'vincentthepoet.com', 'when.com', 'wild4music.com', 'wmconnect.com', 'wow.com',
            'yahoo.at', 'yahoo.be', 'yahoo.cz', 'yahoo.ee', 'yahoo.hu', 'yahoo.nl', 'yahoo.pt',
            'yahoo.sk', 'ygm.com',
            'yahoo.co.il', 'yahoo.co.za', 'yahoo.com.hr'
        ],
    ];

    private function normalizedDeliveryErrorTypeExpression(): string
    {
        $normalizedOutput = "TRIM(REPLACE(REPLACE(REPLACE(REPLACE(deliveries.output, '-', ' '), '\t', ' '), '  ', ' '), '  ', ' '))";

        return "SUBSTRING_INDEX({$normalizedOutput}, ' ', 2)";
    }

    private function normalizeErrorType(string $errorType): string
    {
        return preg_replace('/[\s-]+/', ' ', trim($errorType));
    }

    private function getSuppressionKeepUntil(string $duration): Carbon
    {
        return match ($duration) {
            '7d' => Carbon::now()->addDays(7),
            '1m' => Carbon::now()->addMonth(),
            '1y' => Carbon::now()->addYear(),
            'infinite' => Carbon::now()->addYears(100),
        };
    }

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

        $suppressionCount = DB::connection($connection)
            ->table('suppressions')
            ->where('type', 'recipient')
            ->count();

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
                'suppressionCount' => $suppressionCount,
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
     * Get delivery statistics grouped by the first two SMTP reply tokens.
     */
    public function getBouncesByErrorType(PostalServer $server, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $this->setupConnection($server);
        $connection = $server->getDynamicConnectionName();

        [$startDate, $endDate] = $this->getTimestamp($filters['period'] ?? '30d');
        $errorTypeExpression = $this->normalizedDeliveryErrorTypeExpression();

        $query = DB::connection($connection)
            ->table('deliveries')
            ->join('messages', 'messages.id', '=', 'deliveries.message_id')
            ->selectRaw("
                {$errorTypeExpression} as error_type,
                COUNT(*) as bounce_count,
                COUNT(DISTINCT deliveries.message_id) as unique_messages,
                MAX(deliveries.timestamp) as last_delivery
            ")
            ->whereNotNull('output')
            ->whereRaw("TRIM(output) != ''")
            ->whereRaw("LOCATE(' ', REPLACE(TRIM(deliveries.output), '-', ' ')) > 0")
            ->whereIn('messages.status', ['HardFail', 'Bounced'])
            ->whereBetween('deliveries.timestamp', [$startDate->timestamp, $endDate->timestamp])
            ->groupBy(DB::raw($errorTypeExpression))
            ->orderBy('bounce_count', 'desc');

        if (!empty($filters['q'])) {
            $query->whereRaw("{$errorTypeExpression} LIKE ?", ['%' . $filters['q'] . '%']);
        }

        $currentPage = Paginator::resolveCurrentPage();
        $total = $query->get()->count();

        $results = $query->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(function ($item) {
                return [
                    'error_type' => $item->error_type,
                    'bounce_count' => (int) $item->bounce_count,
                    'unique_messages' => (int) $item->unique_messages,
                    'last_delivery' => Carbon::createFromTimestamp($item->last_delivery)->toDateTimeString()
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

    public function getBounceAddressesByErrorType(PostalServer $server, array $filters = []): array
    {
        $this->setupConnection($server);
        $connection = $server->getDynamicConnectionName();

        [$startDate, $endDate] = $this->getTimestamp($filters['period'] ?? '30d');
        $errorTypeExpression = $this->normalizedDeliveryErrorTypeExpression();
        $errorType = $this->normalizeErrorType($filters['error_type']);

        return DB::connection($connection)
            ->table('deliveries')
            ->join('messages', 'messages.id', '=', 'deliveries.message_id')
            ->selectRaw("
                messages.rcpt_to as address,
                messages.mail_from as from_address,
                messages.subject,
                messages.status,
                deliveries.code as delivery_code,
                deliveries.output as delivery_output,
                {$errorTypeExpression} as error_type,
                FROM_UNIXTIME(deliveries.timestamp) as delivered_at
            ")
            ->whereNotNull('deliveries.output')
            ->whereRaw("TRIM(deliveries.output) != ''")
            ->whereIn('messages.status', ['HardFail', 'Bounced'])
            ->whereBetween('deliveries.timestamp', [$startDate->timestamp, $endDate->timestamp])
            ->whereRaw("{$errorTypeExpression} = ?", [$errorType])
            ->orderByDesc('deliveries.timestamp')
            ->get()
            ->toArray();
    }

    public function suppressBounceAddressesByErrorType(PostalServer $server, array $filters = []): array
    {
        $this->setupConnection($server);
        $connection = $server->getDynamicConnectionName();

        [$startDate, $endDate] = $this->getTimestamp($filters['period'] ?? '30d');
        $errorTypeExpression = $this->normalizedDeliveryErrorTypeExpression();
        $errorType = $this->normalizeErrorType($filters['error_type']);
        $duration = $filters['duration'];
        $keepUntil = $this->getSuppressionKeepUntil($duration);
        $now = (float) Carbon::now()->format('U.u');
        $reason = substr("Bounced with {$errorType}", 0, 255);

        $addresses = DB::connection($connection)
            ->table('deliveries')
            ->join('messages', 'messages.id', '=', 'deliveries.message_id')
            ->whereNotNull('deliveries.output')
            ->whereRaw("TRIM(deliveries.output) != ''")
            ->whereIn('messages.status', ['HardFail', 'Bounced'])
            ->whereBetween('deliveries.timestamp', [$startDate->timestamp, $endDate->timestamp])
            ->whereRaw("{$errorTypeExpression} = ?", [$errorType])
            ->distinct()
            ->pluck('messages.rcpt_to')
            ->filter()
            ->values();

        if ($addresses->isEmpty()) {
            return [
                'error_type' => $errorType,
                'duration' => $duration,
                'matched_addresses' => 0,
                'inserted' => 0,
                'updated' => 0,
                'suppressed' => 0,
                'keep_until' => $keepUntil->toDateTimeString(),
                'keep_until_timestamp' => (float) $keepUntil->format('U.u'),
            ];
        }

        $existingAddresses = DB::connection($connection)
            ->table('suppressions')
            ->where('type', 'recipient')
            ->whereIn('address', $addresses->all())
            ->pluck('address')
            ->unique()
            ->values();

        $updated = 0;

        if ($existingAddresses->isNotEmpty()) {
            $updated = DB::connection($connection)
                ->table('suppressions')
                ->where('type', 'recipient')
                ->whereIn('address', $existingAddresses->all())
                ->update([
                    'reason' => $reason,
                    'timestamp' => $now,
                    'keep_until' => (float) $keepUntil->format('U.u'),
                ]);
        }

        $newAddresses = $addresses->diff($existingAddresses)->values();
        $inserted = 0;

        if ($newAddresses->isNotEmpty()) {
            $rows = $newAddresses->map(function ($address) use ($reason, $now, $keepUntil) {
                return [
                    'type' => 'recipient',
                    'address' => $address,
                    'reason' => $reason,
                    'timestamp' => $now,
                    'keep_until' => (float) $keepUntil->format('U.u'),
                ];
            })->all();

            DB::connection($connection)->table('suppressions')->insert($rows);
            $inserted = count($rows);
        }

        return [
            'error_type' => $errorType,
            'duration' => $duration,
            'matched_addresses' => $addresses->count(),
            'inserted' => $inserted,
            'updated' => $updated,
            'suppressed' => $inserted + $updated,
            'keep_until' => $keepUntil->toDateTimeString(),
            'keep_until_timestamp' => (float) $keepUntil->format('U.u'),
        ];
    }

    /**
     * Get recipient suppressions with search and pagination
     */
    public function getSuppressions(PostalServer $server, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $this->setupConnection($server);
        $connection = $server->getDynamicConnectionName();

        $query = DB::connection($connection)
            ->table('suppressions')
            ->selectRaw('
                id,
                type,
                address,
                reason,
                timestamp,
                keep_until,
                SUBSTRING_INDEX(address, "@", -1) as domain
            ')
            ->where('type', 'recipient');

        if (!empty($filters['q'])) {
            $query->where('address', 'like', '%' . $filters['q'] . '%');
        }

        if (!empty($filters['domain'])) {
            $domain = ltrim($filters['domain'], '@');
            $query->where('address', 'like', '%@' . $domain);
        }

        $query->orderByDesc('timestamp');

        return $query->paginate($perPage);
    }

    /**
     * Remove suppressions by scope, domain, or preset
     */
    public function removeSuppressions(PostalServer $server, array $filters = []): int
    {
        $this->setupConnection($server);
        $connection = $server->getDynamicConnectionName();

        $query = DB::connection($connection)
            ->table('suppressions')
            ->where('type', 'recipient');

        if (!empty($filters['scope']) && $filters['scope'] === 'all') {
            return $query->delete();
        }

        $domains = [];

        if (!empty($filters['preset'])) {
            $preset = strtolower($filters['preset']);
            $domains = self::SUPPRESSION_PRESETS[$preset] ?? [];
        }

        if (!empty($filters['domain'])) {
            $domains[] = ltrim($filters['domain'], '@');
        }

        if (!empty($filters['domains']) && is_array($filters['domains'])) {
            $domains = array_merge($domains, $filters['domains']);
        }

        $domains = array_values(array_filter(array_unique(array_map(function ($domain) {
            return ltrim((string) $domain, '@');
        }, $domains))));

        if (!empty($domains)) {
            return $query->where(function ($builder) use ($domains) {
                foreach ($domains as $domain) {
                    $builder->orWhere('address', 'like', '%@' . $domain);
                }
            })->delete();
        }

        if (!empty($filters['address'])) {
            return $query->where('address', $filters['address'])->delete();
        }

        return 0;
    }

    /**
     * Get built-in suppression presets
     */
    public function getSuppressionPresets(): array
    {
        return self::SUPPRESSION_PRESETS;
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
