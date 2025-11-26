<?php

namespace App\Http\Controllers;

use App\Models\CaughtException;
use Illuminate\Http\Request;

class SlackController extends Controller
{
    public function notify(CaughtException $exception){
        $webhookUrl = config("egg.slack_webhook_url");
        if (!$webhookUrl) {
            dump("Slack webhook URL is not configured.");
            return false;
        }

        if ($exception instanceof CaughtException) {
            $exceptionClass = $exception->exception_class ?? "Not Found";
            $message = $exception->message ?? "Not Found";
            $file = $exception->file ?? "Not Found";
            $line = $exception->line ?? "Not Found";
            $trace = $exception->trace ?? "Not Found";
            $category = $exception->category ?? "Not Found";

            $data = [
                "text" => "Fallback tekst: En alvorlig fejl er sket.",
                "blocks" => [
                    [
                        "type" => "header",
                        "text" => [
                            "type" => "plain_text",
                            "text" => "🚨 New Exception Reported (EggBot)"
                        ]
                    ],
                    [
                        "type" => "section",
                        "fields" => [
                            [
                                "type" => "mrkdwn",
                                "text" => "*Type:*\n`{$exceptionClass}`"
                            ],
                            [
                                "type" => "mrkdwn",
                                "text" => "*Category:*\n`{$category}`"
                            ],
                            [
                                "type" => "mrkdwn",
                                "text" => "*Path:*\n`{$file}:{$line}`"
                            ]
                        ]
                    ],
                    [
                        "type" => "divider"
                    ],
                    [
                        "type" => "section",
                        "text" => [
                            "type" => "mrkdwn",
                            "text" => "*Message:*\n`{$message}`"
                        ]
                    ]
                ]
            ];
            $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        else
        {
            $payload = json_encode([
                "text" => $exception
            ]);
        }

        $options = [
            "http" => [
                "method"  => "POST",
                "header"  => "Content-Type: application/json\r\n",
                "content" => $payload,
                "timeout" => 5,
            ],
        ];
        $context = stream_context_create($options);
        $result = @file_get_contents($webhookUrl, false, $context);
        return $result;
    }
}
