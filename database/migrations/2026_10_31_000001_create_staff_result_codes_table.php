<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_result_codes', function (Blueprint $table) {
            // A one-time code emailed to a staff candidate so they can read their released results. Stored hashed.
            $table->id();
            $table->foreignId('staff_candidate_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['staff_candidate_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_result_codes');
    }
};
