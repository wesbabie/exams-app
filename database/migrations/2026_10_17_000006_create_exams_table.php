<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_category_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('cycle_year')->nullable();

            $table->string('paper_mode', 20)->default('fixed_set'); // fixed_set | pool_draw
            $table->boolean('shuffle_questions')->default(true);
            $table->boolean('shuffle_options')->default(true);
            $table->boolean('forward_only')->default(true);

            // One overall pass mark, compared against the total
            $table->decimal('pass_mark', 6, 2);
            $table->string('pass_mark_type', 12)->default('percentage'); // percentage | absolute
            $table->decimal('negative_mark_fraction', 4, 3)->default(0); // fraction of the question mark deducted for a wrong answer

            $table->unsignedInteger('top_n')->nullable();      // interns: shortlist size
            $table->string('tie_break_rule', 50)->nullable();  // e.g. field_section_score | earliest_submission

            $table->string('result_release', 20)->default('hidden'); // hidden | pass_fail | full_breakdown
            $table->timestamp('results_released_at')->nullable();

            $table->string('status', 20)->default('draft')->index(); // draft | in_review | approved | scheduled | closed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['exam_category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
