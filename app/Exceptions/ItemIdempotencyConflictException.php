<?php

namespace OGame\Exceptions;

use RuntimeException;

/**
 * The same item idempotency key was replayed with a different payload: the
 * second request is not a retry of the first, it is refused.
 */
class ItemIdempotencyConflictException extends RuntimeException
{
    public static function forKey(string $key): self
    {
        return new self('Item idempotency key reused with a different payload: ' . $key);
    }
}
