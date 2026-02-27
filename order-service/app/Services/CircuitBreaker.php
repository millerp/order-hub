<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class CircuitBreaker
{
    public const STATE_CLOSED = 'closed';

    public const STATE_OPEN = 'open';

    public const STATE_HALF_OPEN = 'half_open';

    private string $keyPrefix = 'circuit_breaker:product_service';

    private int $threshold;

    private int $timeoutSeconds;

    public function __construct(?int $threshold = null, ?int $timeoutSeconds = null)
    {
        $this->threshold = $threshold ?? (int) env('CIRCUIT_BREAKER_THRESHOLD', 5);
        $this->timeoutSeconds = $timeoutSeconds ?? (int) env('CIRCUIT_BREAKER_TIMEOUT', 30);
    }

    /**
     * Execute the callable through the circuit breaker.
     * Returns the callable result, or throws / returns 503 response when circuit is OPEN.
     *
     * @param  callable  $callable  Function that performs the HTTP call and returns the response
     * @return mixed Response from callable, or 503 JSON response when circuit is open
     */
    public function call(callable $callable)
    {
        $state = $this->getState();

        if ($state === self::STATE_OPEN) {
            return response()->json([
                'success' => false,
                'message' => 'Service temporarily unavailable',
            ], 503);
        }

        if ($state === self::STATE_HALF_OPEN) {
            try {
                $result = $callable();
                $success = $this->isSuccess($result);
                if ($success) {
                    $this->recordSuccess();
                    $this->setState(self::STATE_CLOSED);
                } else {
                    $this->recordFailure();
                    $this->setState(self::STATE_OPEN);
                    $this->setOpenedAt(time());
                }

                return $result;
            } catch (\Throwable $e) {
                $this->recordFailure();
                $this->setState(self::STATE_OPEN);
                $this->setOpenedAt(time());

                return response()->json([
                    'success' => false,
                    'message' => 'Service temporarily unavailable',
                ], 503);
            }
        }

        // CLOSED: normal flow
        try {
            $result = $callable();
            $success = $this->isSuccess($result);
            if ($success) {
                $this->resetFailureCount();
            } else {
                $this->recordFailure();
                $failures = (int) Redis::get($this->keyPrefix.':failures') ?: 0;
                if ($failures >= $this->threshold) {
                    $this->setState(self::STATE_OPEN);
                    $this->setOpenedAt(time());

                    return response()->json([
                        'success' => false,
                        'message' => 'Service temporarily unavailable',
                    ], 503);
                }
            }

            return $result;
        } catch (\Throwable $e) {
            $this->recordFailure();
            $failures = (int) Redis::get($this->keyPrefix.':failures') ?: 0;
            if ($failures >= $this->threshold) {
                $this->setState(self::STATE_OPEN);
                $this->setOpenedAt(time());
            }

            return response()->json([
                'success' => false,
                'message' => 'Service temporarily unavailable',
            ], 503);
        }
    }

    private function getState(): string
    {
        $state = Redis::get($this->keyPrefix.':state');
        if ($state === null) {
            return self::STATE_CLOSED;
        }
        if ($state === self::STATE_OPEN) {
            $openedAt = (int) Redis::get($this->keyPrefix.':opened_at');
            if ($openedAt && (time() - $openedAt) >= $this->timeoutSeconds) {
                $this->setState(self::STATE_HALF_OPEN);

                return self::STATE_HALF_OPEN;
            }
        }

        return $state;
    }

    private function setState(string $state): void
    {
        Redis::set($this->keyPrefix.':state', $state);
        if ($state === self::STATE_CLOSED) {
            $this->resetFailureCount();
        }
    }

    private function setOpenedAt(int $timestamp): void
    {
        Redis::set($this->keyPrefix.':opened_at', (string) $timestamp);
    }

    private function recordFailure(): void
    {
        Redis::incr($this->keyPrefix.':failures');
    }

    private function recordSuccess(): void
    {
        $this->resetFailureCount();
    }

    private function resetFailureCount(): void
    {
        Redis::set($this->keyPrefix.':failures', '0');
    }

    private function isSuccess($response): bool
    {
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            return $response->getStatusCode() < 500;
        }
        if (method_exists($response, 'successful')) {
            return $response->successful();
        }
        if (method_exists($response, 'getStatusCode')) {
            return $response->getStatusCode() < 500;
        }

        return true;
    }
}
