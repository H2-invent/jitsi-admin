<?php

namespace App\Message;

/**
 * Dispatched with a DelayStamp when an ad-hoc call starts ringing. When it is handled after the
 * configured signaling duration, the callee has not answered and the caller must be informed.
 */
class AdhocCallTimeoutMessage
{
    public function __construct(
        private string $calloutSessionUid,
    ) {
    }

    public function getCalloutSessionUid(): string
    {
        return $this->calloutSessionUid;
    }
}
