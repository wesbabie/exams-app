<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->restrictOnDelete();
            $table->foreignId('exam_category_id')->nullable()->constrained()->restrictOnDelete(); // null = shared
            $table->foreignId('question_group_id')->nullable()->constrained()->nullOnDelete();

            // Versioning: once a question has appeared on a paper, never edit it in place.
            // Create a new row pointing back at the old one, and retire the old row.
            $table->foreignId('previous_version_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->unsignedSmallInteger('version')->default(1);

            $table->string('type', 20)->default('single_choice'); // single_choice | multi_select | true_false
            $table->text('stem');
            $table->string('image_path')->nullable();
            $table->text('explanation')->nullable();
            $table->unsignedSmallInteger('time_seconds')->nullable(); // per-question override of the section timer

            $table->string('status', 20)->default('draft')->index(); // draft | in_review | approved | retired
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->date('review_due_on')->nullable(); // e.g. civil service rules that may change

            $table->timestamps();
            $table->softDeletes();

            $table->index(['topic_id', 'status']);
            $table->index(['exam_category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
