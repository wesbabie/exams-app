<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_registrations', function (Blueprint $table) {
            // Which imported list a registration came from (null for late additions)
            $table->unsignedBigInteger('eligible_list_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('exam_registrations', function (Blueprint $table) {
            $table->dropIndex(['eligible_list_id']);
            $table->dropColumn('eligible_list_id');
        });
    }
};
