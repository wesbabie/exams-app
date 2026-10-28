<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_section_questions', function (Blueprint $table) {
            // The fixed question list for paper_mode = fixed_set (staff promotion exams)
            $table->id();
            $table->foreignId('exam_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->unique(['exam_section_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_section_questions');
    }
};
