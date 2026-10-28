<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_candidates', function (Blueprint $table) {
            // Staff on an eligible list approved by admin staff. Identified by file number.
            $table->id();
            $table->string('file_number', 30)->unique();
            $table->string('surname');
            $table->string('first_name');
            $table->string('other_names')->nullable();

            // Cadre and level as last imported; a person can be promoted, so the exam itself carries the category
            $table->string('cadre')->nullable();
            $table->string('level', 30)->nullable();
            $table->string('department')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();

            // For matching to the HRMS (by file number) once it is ready
            $table->unsignedBigInteger('hrms_employee_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_candidates');
    }
};
