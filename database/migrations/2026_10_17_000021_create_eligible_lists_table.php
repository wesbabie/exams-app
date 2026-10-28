<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eligible_lists', function (Blueprint $table) {
            // One imported eligibility list per exam: who imported it, who approved it, when it was locked
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); // must differ from imported_by
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('locked_at')->nullable(); // after this, changes need a logged reason
            $table->timestamps();

            $table->index('exam_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eligible_lists');
    }
};
