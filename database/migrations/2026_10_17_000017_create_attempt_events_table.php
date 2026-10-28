<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempt_events', function (Blueprint $table) {
            // Integrity and operations log: started, resumed, disconnected, tab_hidden, fullscreen_exit, time_extended, force_submitted...
            $table->id();
            $table->foreignId('attempt_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 40);
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete(); // set for invigilator/admin actions
            $table->timestamp('created_at')->useCurrent();

            $table->index(['attempt_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_events');
    }
};
