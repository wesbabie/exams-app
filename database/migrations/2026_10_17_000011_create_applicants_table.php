<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            // Intern applicants only. Staff candidates come from your existing HRMS tables.
            $table->id();
            $table->foreignId('exam_category_id')->constrained()->restrictOnDelete(); // chosen discipline
            $table->string('application_number', 30)->unique();
            $table->string('surname');
            $table->string('first_name');
            $table->string('other_names')->nullable();
            $table->string('email')->unique();
            $table->string('phone', 30);
            $table->string('professional_reg_no', 50)->nullable();
            $table->string('passport_path')->nullable();
            $table->string('credential_path')->nullable();

            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('documents_verified_at')->nullable();
            $table->foreignId('documents_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('discipline_locked_at')->nullable();

            $table->timestamps();
            $table->softDeletes(); // supports a retention/cleanup rule for unsuccessful applicants
        
            $table->index('exam_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
