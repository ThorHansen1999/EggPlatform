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

class ProcessDatabaseInputs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $slackController = new SlackController();
        $MinutesAgo = Carbon::now()->subMinutes(30);
        $maxCount = 5;
        $records = CaughtException::where('created_at', '>=', $MinutesAgo)
        ->where('category', 'external')
        ->get();

        $groups = $records->groupBy('carrier');

        foreach ($groups as $carrier => $carrierRecords) {
            // $carrier = "bring", "gls", etc.
            // $carrierRecords = all rows for that carrier

           if(count($carrierRecords) >= $maxCount) {

               \Log::info("Processing carrier: $carrier, count: ".$carrierRecords->count());
               // Notify Slack
                $slackController->notifyError($carrier, $carrierRecords->count());
           }
        }
    }
}
