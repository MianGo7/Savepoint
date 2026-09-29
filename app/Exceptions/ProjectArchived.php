<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when work is started or a task is created in an archived project.
 */
final class ProjectArchived extends RuntimeException
{
    public static function make(): self
    {
        return new self(__('The project of this task is archived. No new work can start in it.'));
    }
}
