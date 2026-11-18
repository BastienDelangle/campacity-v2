<?php

namespace App\Payments;

final readonly class PaymentStart
{
    public function __construct(
        public ?string $redirectUrl = null,
        public ?string $instructions = null,
    ) {}
}
