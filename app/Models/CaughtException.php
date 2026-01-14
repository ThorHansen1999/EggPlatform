<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaughtException extends Model
{
    protected $table = 'egg_exceptions';

    protected $fillable = [
        'exception_class',
        'message',
        'code',
        'file',
        'line',
        'trace',
        'category',
        'hash',
        'carrier'
    ];

    protected $casts = [
        'context' => 'array', // Automatically decode JSON
    ];

//    public static function fromException(\Throwable $exception): self
//    {
//        $model = new self();
//        $model->exception_class = get_class($exception);
//        $model->message = $exception->getMessage();
//        $model->file = $exception->getFile();
//        $model->line = $exception->getLine();
//        $model->trace = $exception->getTraceAsString();
//        return $model;
//    }

    public static function fromRequest($request): self
    {
        $model = new self();
        $model->exception_class = $request['exception_class'];
        $model->message = $request['message'];
        $model->code = $request['code'];
        $model->file = $request['file'];
        $model->line = $request['line'];
        $model->trace = $request['trace'];
        
        return $model;
    }
}
