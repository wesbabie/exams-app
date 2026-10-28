<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_paper_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('attempt_no')->default(1);

            $table->string('status', 20)->default('in_progress'); // in_progress | submitted | void
            $table->timestamp('started_at');
            $table->timestamp('submitted_at')->nullable();
            $table->string('submit_reason', 20)->nullable(); // manual | time_expired | admin
            $table->unsignedSmallInteger('current_position')->default(0);

            // One active session per candidate
            $table->string('session_token_hash', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_seen_at')->nullable();

            $table->timestamps();

            $table->unique(['exam_registration_id', 'attempt_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};
