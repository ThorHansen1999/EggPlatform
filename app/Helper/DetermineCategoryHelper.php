<?php

namespace App\Helper;

use App\Models\CaughtException;
use Illuminate\Support\Facades\Log;

class DetermineCategoryHelper
{
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


    // Make method static for easier utility-style usage
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

        Log::info("DetermineCategoryHelper: externalCount={$externalCount}, internalCount={$internalCount}");

        // Prefer external only if strictly greater; ties default to internal
        return $externalCount > $internalCount ? 'external' : 'internal';
    }

    /**
     * Count occurrences of each keyword within the given text, case-insensitive.
     * Uses simple substring matching; for bracketed tokens like 'sqlstate[' it still works.
     */
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
}
