<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            // ADR-0006: stored so that the one open session rule can be an index.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index('task_id');
            $table->index(['user_id', 'started_at']);
        });

        // NFR3: the database itself rejects a second open session of a user,
        // which also covers double submissions that pass an application check.
        DB::statement('create unique index work_sessions_one_open_per_user on work_sessions (user_id) where ended_at is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('work_sessions');
    }
};
