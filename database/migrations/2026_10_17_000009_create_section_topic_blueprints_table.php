<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_topic_blueprints', function (Blueprint $table) {
            // For paper_mode = pool_draw: how many questions to draw per topic
            $table->id();
            $table->foreignId('exam_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('question_count');
            $table->timestamps();

            $table->unique(['exam_section_id', 'topic_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_topic_blueprints');
    }
};
