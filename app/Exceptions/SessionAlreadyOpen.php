<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a second work session would be opened for a developer who
 * already has one.
 */
final class SessionAlreadyOpen extends RuntimeException
{
    public static function forUser(): self
    {
        return new self(__('Another task is already active. Pause it first.'));
    }
}
