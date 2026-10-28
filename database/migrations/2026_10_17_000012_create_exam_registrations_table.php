<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_session_id')->nullable()->constrained()->nullOnDelete();

            // Candidate is either a staff record or an Applicant (register a morph map in a service provider)
            $table->morphs('candidate');

            $table->string('seat_number', 10)->nullable();
            $table->string('status', 20)->default('registered'); // registered | checked_in | admitted | absent | completed
            $table->decimal('extra_time_multiplier', 3, 2)->default(1.00);
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('admitted_at')->nullable(); // candidate cannot log in until an invigilator admits them

            // 6-digit exam PIN: random per registration, hashed, valid for one exam and sitting
            $table->string('pin_hash')->nullable();
            $table->timestamp('pin_issued_at')->nullable();
            $table->timestamp('pin_expires_at')->nullable();
            $table->unsignedTinyInteger('pin_failed_attempts')->default(0);
            $table->timestamp('locked_at')->nullable(); // set after 5 wrong attempts; only an invigilator can unlock
            $table->timestamps();

            $table->unique(['exam_id', 'candidate_type', 'candidate_id'], 'exam_reg_candidate_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_registrations');
    }
};
