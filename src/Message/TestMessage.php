<?php

namespace App\Message;

final class TestMessage
{
    public function __construct(
        public readonly int $randomNumber,
    )
    {
    }
}
