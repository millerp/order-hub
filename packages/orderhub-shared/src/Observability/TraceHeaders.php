<?php

namespace OrderHub\Shared\Observability;

class TraceHeaders
{
    public static function resolveTraceId(?string $traceIdHeader, ?string $traceparentHeader): string
    {
        $traceId = trim((string) $traceIdHeader);
        if ($traceId !== '') {
            return $traceId;
        }

        $fromTraceparent = self::traceIdFromTraceparent($traceparentHeader);
        if ($fromTraceparent !== null) {
            return $fromTraceparent;
        }

        return self::uuidV4();
    }

    public static function traceIdFromTraceparent(?string $traceparent): ?string
    {
        $traceparent = trim((string) $traceparent);
        if ($traceparent === '') {
            return null;
        }

        $parts = explode('-', $traceparent);
        if (count($parts) < 4 || strlen($parts[1]) !== 32) {
            return null;
        }

        $hex = strtolower($parts[1]);
        if (! ctype_xdigit($hex)) {
            return null;
        }

        return substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-'.substr($hex, 12, 4).'-'.substr($hex, 16, 4).'-'.substr($hex, 20, 12);
    }

    public static function traceparentFromTraceId(string $traceId): string
    {
        $normalizedTraceId = strtolower(str_replace('-', '', $traceId));
        $normalizedTraceId = str_pad(substr($normalizedTraceId, 0, 32), 32, '0');

        $spanId = bin2hex(random_bytes(8));

        return sprintf('00-%s-%s-01', $normalizedTraceId, $spanId);
    }

    public static function resolveFromPayloadAndHeaders(array $payload, array $headers): string
    {
        $payloadTraceId = isset($payload['trace_id']) ? (string) $payload['trace_id'] : null;
        $headerTraceId = isset($headers['x-trace-id']) ? (string) $headers['x-trace-id'] : null;
        $traceparent = isset($headers['traceparent']) ? (string) $headers['traceparent'] : null;

        return self::resolveTraceId($payloadTraceId ?: $headerTraceId, $traceparent);
    }

    private static function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-'.substr($hex, 12, 4).'-'.substr($hex, 16, 4).'-'.substr($hex, 20, 12);
    }
}
