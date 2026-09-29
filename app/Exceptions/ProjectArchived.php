<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when work is started on a task whose project has been archived.
 */
final class ProjectArchived extends RuntimeException
{
    public static function make(): self
    {
        return new self(__('The project of this task is archived. Restore it before working on the task.'));
    }
}
