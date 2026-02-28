<?php

namespace App\Domain;

use InvalidArgumentException;

class PaymentApprovedPayload
{
    public function __construct(
        public readonly string $paymentId,
        public readonly string $orderId,
        public readonly string $eventId,
        public readonly ?string $occurredAt = null,
        public readonly ?string $traceId = null,
    ) {}

    public static function fromArray(array $payload): self
    {
        $paymentId = trim((string) ($payload['payment_id'] ?? ''));
        $orderId = trim((string) ($payload['order_id'] ?? ''));
        $eventId = trim((string) ($payload['event_id'] ?? ''));
        $occurredAt = isset($payload['occurred_at']) ? (string) $payload['occurred_at'] : null;
        $traceId = isset($payload['trace_id']) ? trim((string) $payload['trace_id']) : null;

        if ($paymentId === '' || $orderId === '' || $eventId === '') {
            throw new InvalidArgumentException('Invalid payment.approved payload: required keys are payment_id, order_id, event_id.');
        }

        return new self($paymentId, $orderId, $eventId, $occurredAt, $traceId);
    }
}
