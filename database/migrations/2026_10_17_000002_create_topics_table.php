<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            // null = shared topic (e.g. General Knowledge, Civil Service Rules)
            $table->foreignId('exam_category_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['exam_category_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
