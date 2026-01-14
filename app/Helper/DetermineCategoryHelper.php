<?php

namespace App\Helper;

use App\Models\CaughtException;
use Prism\Prism\Facades\Prism;

class DetermineCategoryHelper
{
    // Keywords indicating external issues
    public const EXTERNAL_KEYWORDS = [
        // Network/IO
        'network', 'timeout', 'timed out', 'connection refused', 'connection reset', 'socket', 'dns', 'lookup', 'host unreachable', 'broken pipe',
        // HTTP/API
        'http', 'api', 'endpoint', 'upstream', 'gateway', 'proxy', 'service unavailable', 'bad gateway', '502', '503', '504', '429', 'rate limit', 'too many requests',
        // TLS/Certificates/Auth to external
        'ssl', 'tls', 'certificate', 'cert verify failed', 'handshake', 'oauth', 'token', 'unauthorized', 'forbidden', '401', '403',
        // Cloud/3rd-party services
        's3', 'aws', 'gcs', 'azure', 'firebase', 'stripe', 'paypal', 'twilio', 'sendgrid', 'mailgun', 'smtp', 'imap', 'redis cluster', 'kafka',
        // Database external (remote service indicators)
        'read replica', 'replica', 'rds', 'cloud sql', 'managed db', 'pdoexception', 'queryexception', 'sqlstate[', 'redisexception',
        // External storage/files
        'ftp', 'sftp', 'webdav', 'nfs', 'mount', 'filesystem read-only',
        // External messaging/webhook
        'webhook', 'third-party', 'external', 'remote service', 'provider', 'integration',
        // Vendor / client libraries
        '/vendor/', 'guzzle', 'httpclient', 'curl error',
        // HTTP 5xx codes
        '500', '501', '502', '503', '504', '505', '5xx',
    ];

    // Keywords indicating internal issues
    public const INTERNAL_KEYWORDS = [
        // Language/runtime
        'null pointer', 'call to a member function on null', 'undefined variable', 'undefined index', 'type error', 'type mismatch', 'argument count', 'invalid argument', 'division by zero', 'out of bounds', 'index out of range', 'overflow', 'underflow', 'assertion failed', 'assert', 'invariant', 'illegal state', 'not implemented', 'todo', 'deprecated',
        // Application logic/config
        'logic error', 'state error', 'precondition failed', 'postcondition failed', 'configuration error', 'env missing', 'missing environment', 'config not found', 'feature flag',
        // Framework/library usage
        'class not found', 'interface not found', 'trait not found', 'autoload', 'composer', 'binding resolution', 'container', 'service not found', 'route not found', 'view not found', 'blade',
        // Database schema/query (internal app issues)
        'migration missing', 'column not found', 'table not found', 'unknown column', 'constraint violation', 'foreign key', 'unique constraint', 'sql syntax', 'query exception',
        // Files/permissions local
        'permission denied', 'read-only filesystem', 'disk full', 'no space left on device', 'path not found', 'file not found',
        // Concurrency
        'deadlock', 'race condition', 'lock timeout',
        // App code paths
        '/app/', 'app\\',
    ];


    // Determine category based on keyword heuristics
    public static function determineCategory(CaughtException $exception): string
    {
        $class = $exception->exception_class ?? '';
        $file = $exception->file ?? '';
        $trace = $exception->trace ?? '';
        $code = $exception->code ?? null;
        $message = $exception->message ?? '';

        // Build a single searchable text blob (lowercased) including code as string
        $blob = strtolower(implode("\n", [
            (string) $class,
            (string) $file,
            (string) $trace,
            (string) $message,
            $code !== null ? (string) $code : ''
        ]));

        $externalCount = self::countKeywordMatches(self::EXTERNAL_KEYWORDS, $blob);
        $internalCount = self::countKeywordMatches(self::INTERNAL_KEYWORDS, $blob);

        \Log::info("DetermineCategoryHelper: externalCount={$externalCount}, internalCount={$internalCount}");

        // If counts are equal, defer to AI-based determination
        if ($externalCount === $internalCount) {
            return self::determineCategoryWithAI($exception);
        }

        // Prefer external only if strictly greater; ties default to internal
        return $externalCount > $internalCount ? 'external' : 'internal';
    }

    // Count occurrences of any of the keywords in the given text
    private static function countKeywordMatches(array $keywords, string $text): int
    {
        $count = 0;
        foreach ($keywords as $kw) {
            $needle = strtolower($kw);
            // substr_count counts non-overlapping occurrences; good enough for scoring
            $count += substr_count($text, $needle);
        }
        return $count;
    }

    // Use AI to determine category when heuristic is inconclusive
    public static function determineCategoryWithAI(CaughtException $exception): string
    {
        // Old prompt
//        $prompt = "Respond with either External or Internal (only those words, no filler response) based
//           on whether the following exception is caused by external factors (like user input, network issues, third-party services)
//           or internal factors (like bugs in the code, server issues).
//           Determine whether or not the exceptions are caused by third party integration downtime.
//           Exception information: {$exception}";

        // New prompt with deterministic rules
        $prompt = "Classify the exception strictly using the following deterministic rules:

            1. If the file path or class name contains 'Carriers' or a known carrier name
               (e.g., Bring, DAO, GLS, PostNord, UPS, FedEx), classify as External.
               These paths indicate integration with external providers.

            2. If the exception message or trace contains keywords related to network issues
               (timeout, connection refused, DNS, cURL error, 5xx from external service), classify as External.

            3. If the error occurs inside business logic outside the Carriers/ folder
               and has no signs of external systems, classify as Internal.

            4. If the cause is unclear or ambiguous, default to Internal.

            Only respond with a single word: External or Internal.

            Exception:
            {$exception}";

        \Log::info("DetermineCategoryHelper AI prompt: " . $prompt);

        $provider = config('egg.ai_provider');
        $model = config("egg.ai_model");

        $response = Prism::text()
           ->using($provider, $model)
           ->withPrompt($prompt)
            ->withClientOptions(['timeout' => 300])
           ->asText();

        \Log::info("DetermineCategoryHelper AI response: " . $response->text);

        // Return based on AI response, default to uncategorized if unclear
        $text = strtolower($response->text);
        if (str_contains($text, 'external')) {
            return 'ExternalAI';
        }
        if (str_contains($text, 'internal')) {
            return 'InternalAI';
        }
        return 'Uncategorized';
    }
}
