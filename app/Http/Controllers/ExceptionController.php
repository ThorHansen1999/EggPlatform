<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessExceptionJob;
use Illuminate\Http\Request;

class ExceptionController extends Controller
{
    public function report(Request $request)
    {
        $validated = $request->validate([
            'exception_class' => 'required|string',
            'message' => 'required|string',
            'code' => 'required',
            'file' => 'required',
            'line' => 'required',
            'trace' => 'required|string',
        ]);

        // Dispatch a job to Horizon/Redis queue
        ProcessExceptionJob::dispatch($validated);

        // Immediate response to the client
        return response()->json(['status' => 'Exception reported successfully'], 200);
    }
}
