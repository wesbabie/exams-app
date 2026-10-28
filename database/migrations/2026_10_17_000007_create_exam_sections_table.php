<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->string('title'); // General Knowledge | Civil Service Rules | Field-Related
            $table->unsignedTinyInteger('position');
            $table->text('instructions')->nullable();
            $table->unsignedSmallInteger('question_count');
            $table->unsignedSmallInteger('seconds_per_question')->default(60);
            $table->decimal('marks_per_question', 5, 2)->default(1); // same mark for every question
            $table->timestamps();

            $table->unique(['exam_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_sections');
    }
};
