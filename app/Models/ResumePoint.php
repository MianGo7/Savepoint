<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ResumePointFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The note recorded when a work session is paused: where the work stopped and
 * what comes next. It is owned by the developer through its task.
 *
 * @property int $id
 * @property int $task_id
 * @property int $work_session_id
 * @property string $where_stopped
 * @property string $next_step
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['where_stopped', 'next_step'])]
class ResumePoint extends Model
{
    /** @use HasFactory<ResumePointFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * @return BelongsTo<WorkSession, $this>
     */
    public function workSession(): BelongsTo
    {
        return $this->belongsTo(WorkSession::class);
    }
}
