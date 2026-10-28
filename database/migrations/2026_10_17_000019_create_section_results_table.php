<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_section_id')->constrained()->restrictOnDelete();
            $table->decimal('marks', 7, 2);
            $table->decimal('max_marks', 7, 2);
            $table->unsignedSmallInteger('correct_count')->default(0);
            $table->unsignedSmallInteger('wrong_count')->default(0);
            $table->unsignedSmallInteger('unanswered_count')->default(0);
            $table->timestamps();

            $table->unique(['result_id', 'exam_section_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_results');
    }
};
