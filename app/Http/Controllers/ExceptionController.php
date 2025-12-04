<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessExceptionJob;
use Illuminate\Http\Request;

class ExceptionController extends Controller
{
    public function report(Request $request)
    {
        dd("test");

//        $validated = $request->validate([
//            'exception_class' => 'required|string',
//            'message' => 'required|string',
//            'code' => 'required',
//            'file' => 'required',
//            'line' => 'required',
//            'trace' => 'required|string',
//        ]);

        $validated = $request->all();
        \Log::debug($validated);

        // Dispatch a job to Horizon/Redis queue
        ProcessExceptionJob::dispatch($validated);

        // Immediate response to the client
        return response()->json(['status' => 'Exception reported successfully'], 200);

//        $slackController = new SlackController();
//
//        // Custom logic to log exception to database or external service can be added here
//        $exception = CaughtException::fromRequest($validated);
//
//
//        $exception->category = "internal"; // You can categorize exceptions if needed
//        $exception->hash = md5($exception->message . $exception->file . $exception->line);
//        $exception->save();
//
//        $result = $slackController->notify($exception);
    }
}
