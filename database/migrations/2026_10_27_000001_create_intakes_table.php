<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intakes', function (Blueprint $table) {
            // One intern registration window. The year matches exams.cycle_year for the intake's exams.
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('year')->index();
            $table->timestamp('opens_at');
            $table->timestamp('closes_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['opens_at', 'closes_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intakes');
    }
};
