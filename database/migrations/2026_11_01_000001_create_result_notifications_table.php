<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_notifications', function (Blueprint $table) {
            // One line per candidate per release: did we email them that their result is out?
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_registration_id')->constrained()->cascadeOnDelete();
            $table->string('email')->nullable();
            $table->string('status', 12);                 // sent | failed | no_email
            $table->string('error')->nullable();
            $table->timestamp('released_at');             // which release this is about; a re-release starts afresh
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['exam_registration_id', 'released_at']);
            $table->index(['exam_id', 'released_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_notifications');
    }
};
