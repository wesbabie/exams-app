<?php

use App\Support\AuditCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            // One place for "who did what and when". Rows are only ever added.
            $table->id();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name')->nullable();   // kept so the trail still reads well if the account changes
            $table->string('actor_role', 30)->nullable();

            $table->string('category', 20);
            $table->string('action', 80);
            $table->string('summary', 500);

            $table->string('subject_type', 30)->nullable(); // exam, question, sitting, registration, user ...
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();
            $table->unsignedBigInteger('exam_id')->nullable(); // plain column on purpose: the trail outlives the exam

            $table->text('reason')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->index(['category', 'created_at']);
            $table->index(['actor_id', 'created_at']);
            $table->index(['exam_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });

        $this->backfill();
    }

    /** Bring the two older logs across so the history is not lost. */
    private function backfill(): void
    {
        $names = DB::table('users')->pluck('name', 'id');
        $roles = DB::table('users')->pluck('role', 'id');

        if (Schema::hasTable('registration_logs')) {
            DB::table('registration_logs')->orderBy('id')->chunk(500, function ($rows) use ($names, $roles) {
                DB::table('audit_logs')->insert($rows->map(function ($r) use ($names, $roles) {
                    [$category, $text] = AuditCatalog::legacy($r->action);

                    return [
                        'created_at' => $r->created_at,
                        'actor_id' => $r->user_id,
                        'actor_name' => $names[$r->user_id] ?? null,
                        'actor_role' => $roles[$r->user_id] ?? null,
                        'category' => $category,
                        'action' => 'reg.'.$r->action,
                        'summary' => mb_substr($text.($r->detail ? ' · '.$r->detail : ''), 0, 480),
                        'subject_type' => $r->exam_registration_id ? 'registration' : null,
                        'subject_id' => $r->exam_registration_id,
                        'subject_label' => $r->detail,
                        'exam_id' => $r->exam_id,
                        'reason' => $r->reason,
                        'meta' => null,
                        'ip_address' => null,
                    ];
                })->all());
            });
        }

        if (Schema::hasTable('staff_logs')) {
            DB::table('staff_logs')->orderBy('id')->chunk(500, function ($rows) use ($names, $roles) {
                DB::table('audit_logs')->insert($rows->map(fn ($r) => [
                    'created_at' => $r->created_at,
                    'actor_id' => $r->actor_id,
                    'actor_name' => $names[$r->actor_id] ?? null,
                    'actor_role' => $roles[$r->actor_id] ?? null,
                    'category' => 'accounts',
                    'action' => 'staff.'.$r->action,
                    'summary' => mb_substr(AuditCatalog::staff($r->action).($r->detail ? ' · '.$r->detail : ''), 0, 480),
                    'subject_type' => $r->target_id ? 'user' : null,
                    'subject_id' => $r->target_id,
                    'subject_label' => $names[$r->target_id] ?? null,
                    'exam_id' => null,
                    'reason' => null,
                    'meta' => null,
                    'ip_address' => null,
                ])->all());
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
