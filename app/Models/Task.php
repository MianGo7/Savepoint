<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Carbon\CarbonImmutable;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A unit of development work in a project, moved through its lifecycle by the
 * task actions.
 *
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property string $title
 * @property string|null $description
 * @property int|null $estimate_minutes
 * @property string|null $branch_name
 * @property TaskStatus $status
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['title', 'description', 'estimate_minutes', 'branch_name'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * ADR-0008: the characters of a branch name, which must not start with a
     * hyphen, so that it can never be read as an option of git. The D modifier
     * stops the dollar anchor from accepting a trailing line break, which would
     * let a pasted command run without confirmation.
     */
    public const BRANCH_PATTERN = '/^[A-Za-z0-9._\/][A-Za-z0-9._\/-]*$/D';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<WorkSession, $this>
     */
    public function workSessions(): HasMany
    {
        return $this->hasMany(WorkSession::class);
    }

    /**
     * @return HasMany<ResumePoint, $this>
     */
    public function resumePoints(): HasMany
    {
        return $this->hasMany(ResumePoint::class);
    }

    /**
     * @return HasOne<WorkSession, $this>
     */
    public function openWorkSession(): HasOne
    {
        return $this->hasOne(WorkSession::class)->whereNull('ended_at');
    }

    /**
     * The command that checks out the branch of the task, or null when the task
     * has no branch or the stored name does not match the allowed pattern.
     * Implements FR10.
     */
    public function switchCommand(): ?string
    {
        if ($this->branch_name === null || preg_match(self::BRANCH_PATTERN, $this->branch_name) !== 1) {
            return null;
        }

        return 'git switch '.$this->branch_name;
    }

    /**
     * @return HasOne<ResumePoint, $this>
     */
    public function latestResumePoint(): HasOne
    {
        return $this->hasOne(ResumePoint::class)->latestOfMany();
    }
}
