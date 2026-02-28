<?php

namespace Tests\Unit;

use App\Domain\PaymentApprovedPayload;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PaymentApprovedPayloadTest extends TestCase
{
    public function test_it_builds_payload_from_valid_array(): void
    {
        $payload = PaymentApprovedPayload::fromArray([
            'payment_id' => '12',
            'order_id' => '99',
            'event_id' => 'evt-123',
            'occurred_at' => '2026-02-28T18:00:00Z',
        ]);

        $this->assertSame('12', $payload->paymentId);
        $this->assertSame('99', $payload->orderId);
        $this->assertSame('evt-123', $payload->eventId);
    }

    public function test_it_rejects_invalid_payload_contract(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PaymentApprovedPayload::fromArray([
            'payment_id' => '',
            'order_id' => '99',
        ]);
    }
}
