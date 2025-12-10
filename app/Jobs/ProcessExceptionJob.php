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

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
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

    public function determineCategory(CaughtException $exception): string
    {
        $class = $exception->exception_class ?? '';
        $file = $exception->file ?? '';
        $trace = $exception->trace ?? '';
        $code = $exception->code ?? null;
        $message = $exception->message ?? '';

        if (str_contains($file, '/vendor/')) {
            return 'external';
        }

        if (
            str_contains($class, 'Guzzle') ||
            str_contains($class, 'HttpClient') ||
            str_contains($message, 'cURL error') ||
            str_contains($message, 'timed out') ||
            str_contains($message, 'Connection refused')
        ) {
            return 'external';
        }

        if (
            str_contains($class, 'PDOException') ||
            str_contains($class, 'QueryException') ||
            str_contains($message, 'SQLSTATE[') ||
            str_contains($message, 'RedisException')
        ) {
            return 'external';
        }

        if (is_numeric($code) && $code >= 500 && $code <= 599) {
            return 'external';
        }

//        if (
//            str_contains($file, '/app/') ||
//            str_contains($class, 'App\\')
//        ) {
//            return 'internal';
//        }

        // Default safe assumption
        return 'internal';
    }
}
