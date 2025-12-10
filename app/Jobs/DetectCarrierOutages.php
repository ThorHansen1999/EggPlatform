<?php

namespace App\Jobs;

use App\Models\CaughtException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use App\Http\Controllers\SlackController;

class DetectCarrierOutages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $slackController = new SlackController();
        $MinutesAgo = Carbon::now()->subMinutes(30);
        $maxCount = 5;

        // Fetch records from the last 30 minutes with specified categories
        $records = CaughtException::where('created_at', '>=', $MinutesAgo)
            ->whereIn('category', ['external', 'ExternalAI'])
            ->get();

        \Log::info("Total records found: ".$records->count());

        // Group records by carrier
        $groups = $records->groupBy('carrier');

        // Log grouped counts and notify if threshold exceeded
        foreach ($groups as $carrier => $carrierRecords) {
           if(count($carrierRecords) >= $maxCount) {
               \Log::info("Processing carrier: $carrier, count: ".$carrierRecords->count());
               $slackController->notifyError($carrier, $carrierRecords->count());
           }
        }
    }
}
