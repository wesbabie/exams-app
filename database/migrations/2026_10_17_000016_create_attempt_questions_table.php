<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempt_questions', function (Blueprint $table) {
            // One row per question served to a candidate: stores their shuffled order, the server deadline and their answer
            $table->id();
            $table->foreignId('attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_paper_question_id')->constrained()->restrictOnDelete();
            $table->foreignId('exam_section_id')->constrained()->restrictOnDelete();

            $table->unsignedSmallInteger('position');  // this candidate's shuffled order
            $table->json('option_order');              // option ids in the order shown to this candidate
            $table->unsignedSmallInteger('seconds_allowed'); // includes any extra-time multiplier
            $table->timestamp('served_at')->nullable();
            $table->timestamp('deadline_at')->nullable();    // authoritative server-side deadline

            $table->json('selected_option_ids')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->string('status', 20)->default('pending'); // pending | answered | timed_out
            $table->boolean('is_correct')->nullable();
            $table->decimal('marks_awarded', 6, 2)->nullable();
            $table->timestamps();

            $table->unique(['attempt_id', 'position']);
            $table->unique(['attempt_id', 'exam_paper_question_id']);
            $table->index(['attempt_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_questions');
    }
};
