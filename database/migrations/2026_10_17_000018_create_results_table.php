<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('exam_registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete(); // denormalised for ranking queries

            $table->decimal('total_marks', 7, 2);
            $table->decimal('max_marks', 7, 2);
            $table->decimal('percentage', 5, 2);
            $table->boolean('passed');
            $table->unsignedInteger('rank_position')->nullable(); // rank within the exam (cadre/level or discipline)
            $table->boolean('shortlisted')->default(false);
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(['exam_id', 'rank_position']);
            $table->index(['exam_id', 'total_marks']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
