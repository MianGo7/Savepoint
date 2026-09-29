<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\WorkSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A span of time in which a developer worked on one task, open while
 * `ended_at` is null. The time report is derived from these records.
 *
 * @property int $id
 * @property int $task_id
 * @property int $user_id
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $ended_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class WorkSession extends Model
{
    /** @use HasFactory<WorkSessionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasOne<ResumePoint, $this>
     */
    public function resumePoint(): HasOne
    {
        return $this->hasOne(ResumePoint::class);
    }
}
