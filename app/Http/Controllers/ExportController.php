<?php

namespace App\Http\Controllers;

use App\Models\PostalServer;
use App\Services\PostalService;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExportController extends Controller
{
    use ApiResponseTrait;

    protected PostalService $postalService;

    public function __construct(PostalService $postalService)
    {
        $this->postalService = $postalService;
    }

    public function bounces(Request $request, PostalServer $postalServer)
    {
        try {
            if (!$postalServer->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Server is not active'
                ], 422);
            }

            $validated = $request->validate([
                'period' => 'nullable|string|in:1d,7d,14d,30d,today,yesterday',
                'domain' => 'nullable|string|max:255',
            ]);

            $bounceData = $this->postalService->exportBounceData($postalServer, $validated);
            
            $csvContent = $this->generateBounceCSV($bounceData);
            
            $period = $validated['period'] ?? '30d';

            $endDate = Carbon::now();
            $startDate = match ($period) {
                '1d' => $endDate->copy()->subDay(),
                '7d' => $endDate->copy()->subDays(7),
                '14d' => $endDate->copy()->subDays(14),
                '30d' => $endDate->copy()->subDays(30),
                'today' => $endDate->copy()->startOfDay(),
                'yesterday' => $endDate->copy()->subDay()->startOfDay(),
                default => $endDate->copy()->subDays(30), // Default to 30 days
            };

            if ($period === 'yesterday') {
                $endDate = $endDate->copy()->subDay()->endOfDay();
            }

            $filename = "bounces_{$postalServer->name}_{$startDate}_to_{$endDate}.csv";
            
            return response($csvContent)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
                
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->productionSafeErrorResponse($e, 'Failed to export bounce data', 500);
        }
    }

    private function generateBounceCSV(array $bounceData): string
    {
        $csv = "To Address,From Address,Subject,Date\n";
        
        foreach ($bounceData as $bounce) {
            $csv .= sprintf(
                '"%s","%s","%s","%s"' . "\n",
                $this->escapeCsvField($bounce->to_address ?? ''),
                $this->escapeCsvField($bounce->from_address ?? ''),
                $this->escapeCsvField($bounce->subject ?? ''),
                $bounce->sent_at ?? ''
            );
        }
        
        return $csv;
    }

    private function escapeCsvField(string $field): string
    {
        // Replace double quotes with two double quotes for CSV escaping
        return str_replace('"', '""', $field);
    }
}
