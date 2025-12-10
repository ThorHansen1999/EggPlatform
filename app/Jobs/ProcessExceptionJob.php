<?php

namespace App\Jobs;

use App\Models\CaughtException;
use App\Http\Controllers\SlackController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Helper\DetermineCategoryHelper;

class ProcessExceptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $data;

    // Create a new job instance.
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    // Execute the job.
    public function handle(): void
    {
        // Save to database
        $exception = new CaughtException();
        $exception->exception_class = $this->data['exception_class'];
        $exception->message = $this->data['message'];
        $exception->code = $this->data['code'];
        $exception->file = $this->data['file'];
        $exception->line = $this->data['line'];
        $exception->trace = $this->data['trace'];

        // Determine category
        $exception->category = DetermineCategoryHelper::determineCategoryWithAI($exception);
//        $exception->category = $this->determineCategory($exception);
        $exception->hash = md5($exception->message . $exception->file . $exception->line);

        if(str_contains($exception->file, 'Carriers'))
        {
            $fileArray = explode('/', $exception->file);
            $carrierIndex = array_search("Carriers", $fileArray);
            $exception->carrier = $fileArray[$carrierIndex + 1];
        }

        dump($exception);
        $exception->save();

        // Notify Slack
        $slackController = new SlackController();
        $slackController->notify($exception);
    }
}
