<?php
use App\Http\Controllers\SlackController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExceptionController;

Route::post('/exception', [ExceptionController::class, 'report']);

Route::post('/test', function() {
    dd("test");
});
