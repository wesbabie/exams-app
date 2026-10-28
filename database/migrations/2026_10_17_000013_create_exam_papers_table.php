<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_papers', function (Blueprint $table) {
            // One frozen paper per session (a copy of the fixed set, or a blueprint draw from the pool)
            $table->id();
            $table->foreignId('exam_session_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('seed')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('frozen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_papers');
    }
};
