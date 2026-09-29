<?php

namespace App\Exceptions;

use App\Enums\TaskStatus;
use RuntimeException;

/**
 * Thrown when a lifecycle action is applied to a task whose state does not
 * permit it.
 */
final class TaskTransitionNotAllowed extends RuntimeException
{
    public static function from(TaskStatus $status, string $transition): self
    {
        return new self(__('A task with the status :status cannot be :transition.', [
            'status' => $status->value,
            'transition' => $transition,
        ]));
    }

    public static function withoutActiveTask(): self
    {
        return new self(__('There is no active task to switch from.'));
    }

    public static function withoutOpenSession(): self
    {
        return new self(__('The active task has no open work session.'));
    }
}
