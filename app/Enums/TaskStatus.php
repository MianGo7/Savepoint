<?php

namespace App\Enums;

/**
 * The lifecycle state of a task, stored on the task so that the overview can
 * filter on it.
 */
enum TaskStatus: string
{
    case Todo = 'todo';
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Todo => __('To do'),
            self::Active => __('Active'),
            self::Paused => __('Paused'),
            self::Completed => __('Completed'),
        };
    }
}
