<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a task, or a project that contains one, is deleted while a task
 * is active, which would discard its open work session.
 */
final class ActiveTaskCannotBeDeleted extends RuntimeException
{
    public static function make(): self
    {
        return new self(__('An active task cannot be deleted. Pause or complete it first.'));
    }
}
