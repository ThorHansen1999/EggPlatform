<?php
use App\Http\Controllers\SlackController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExceptionController;

Route::post('/slack/message', [SlackController::class, 'notify']);

Route::post('/exception', [ExceptionController::class, 'report']);