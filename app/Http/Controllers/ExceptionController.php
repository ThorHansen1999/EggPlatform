<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CaughtException;

class ExceptionController extends Controller
{
    public function report(Request $request)
    {
        $slackController = new SlackController();

        $validated = $request->validate([
            'exception_class' => 'required|string',
            'message' => 'required|string',
            'file' => 'required',
            'line' => 'required',
            'trace' => 'required|string',
        ]);

        // Custom logic to log exception to database or external service can be added here
        $exception = CaughtException::fromRequest($validated);
        

        $exception->category = "internal"; // You can categorize exceptions if needed
        $exception->hash = md5($exception->message . $exception->file . $exception->line);
        // $exception->save();

        $result = $slackController->notify($exception);
        dump($exception);
    }
}
