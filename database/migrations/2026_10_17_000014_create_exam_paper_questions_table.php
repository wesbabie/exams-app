<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_paper_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_paper_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_section_id')->constrained()->restrictOnDelete();
            $table->foreignId('question_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('position'); // base order before per-candidate shuffling

            // Snapshotted at generation so later setting changes never alter a paper already sat
            $table->decimal('marks', 5, 2);
            $table->unsignedSmallInteger('seconds_allowed');
            $table->timestamps();

            $table->unique(['exam_paper_id', 'question_id']);
            $table->index(['exam_paper_id', 'exam_section_id', 'position'], 'epq_paper_section_pos_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_paper_questions');
    }
};
