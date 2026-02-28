<?php

namespace App\Events;

use App\Domain\PaymentApprovedPayload;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentApprovedReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PaymentApprovedPayload $payload,
    ) {}
}
