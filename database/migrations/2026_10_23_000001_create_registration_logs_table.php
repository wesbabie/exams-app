<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_logs', function (Blueprint $table) {
            // A simple trail of who did what to an exam's candidate list
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('exam_registration_id')->nullable()->index();
            $table->unsignedBigInteger('eligible_list_id')->nullable();
            $table->string('action', 40);   // list_imported, list_approved, list_discarded, candidate_added, candidate_removed, assigned, unassigned, auto_assigned
            $table->string('detail')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['exam_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_logs');
    }
};
