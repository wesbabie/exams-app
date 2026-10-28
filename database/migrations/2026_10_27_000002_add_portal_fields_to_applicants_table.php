<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            // Applicants now sign up first and choose a discipline afterwards
            $table->foreignId('exam_category_id')->nullable()->change();

            $table->foreignId('intake_id')->nullable()->after('id')->constrained('intakes')->restrictOnDelete();
            $table->string('password')->nullable();
            $table->rememberToken();

            // draft | submitted | returned | approved | rejected
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable(); // reason shown to the applicant when rejected or returned

            // Email one-time code (stored hashed)
            $table->string('email_otp_hash')->nullable();
            $table->timestamp('email_otp_sent_at')->nullable();
            $table->timestamp('email_otp_expires_at')->nullable();
            $table->unsignedTinyInteger('email_otp_attempts')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropForeign(['intake_id']);
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'intake_id', 'reviewed_by', 'password', 'remember_token', 'status', 'submitted_at', 'reviewed_at', 'review_note',
                'email_otp_hash', 'email_otp_sent_at', 'email_otp_expires_at', 'email_otp_attempts',
            ]);
        });
    }
};
