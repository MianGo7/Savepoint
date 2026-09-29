<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a task is paused without stating where the work stopped and what
 * comes next.
 */
final class ResumePointRequired extends RuntimeException
{
    public static function make(): self
    {
        return new self(__('A resume point needs both where the work stopped and the next step.'));
    }
}
