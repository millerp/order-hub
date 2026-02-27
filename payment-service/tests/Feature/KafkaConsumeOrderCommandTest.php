<?php

namespace Tests\Feature;

use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KafkaConsumeOrderCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_idempotency_avoids_duplicates()
    {
        $payment = Payment::create([
            'order_id' => 999,
            'amount' => 100.50,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('payments', ['order_id' => 999]);
        $this->assertEquals('approved', $payment->status);
        $this->assertDatabaseCount('payments', 1);

        // Asserting constraints on unique order_id
        $this->expectException(\Illuminate\Database\QueryException::class);
        Payment::create([
            'order_id' => 999,
            'amount' => 200.00,
            'status' => 'failed',
        ]);
    }
}
