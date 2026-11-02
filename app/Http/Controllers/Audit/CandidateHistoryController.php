<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\AttemptEvent;
use App\Models\AuditLog;
use App\Models\ExamRegistration;
use App\Support\AuditCatalog;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CandidateHistoryController extends Controller
{
    public function show(ExamRegistration $registration): View
    {
        $registration->load(['exam', 'examSession', 'attempts']);

        $candidate = $registration->candidate;
        $name = $candidate?->full_name ?? 'Candidate';
        $number = $candidate?->file_number ?? $candidate?->application_number;

        // What staff did to this registration
        $items = AuditLog::where('subject_type', 'registration')->where('subject_id', $registration->id)->get()->map(fn (AuditLog $l) => [
            'at' => $l->created_at,
            'who' => $l->actor_name ?? 'System',
            'text' => preg_replace('/ · .*/', '', $l->summary),
            'detail' => $l->reason,
            'kind' => $l->isSensitive() ? 'warn' : 'staff',
            'ip' => $l->ip_address,
        ]);

        // What happened during the exam itself
        $events = AttemptEvent::whereIn('attempt_id', $registration->attempts->pluck('id'))->orderBy('id')->get()->map(function (AttemptEvent $e) use ($name) {
            $meta = $e->meta ?? [];
            $text = AuditCatalog::ATTEMPT_EVENTS[$e->event_type] ?? ucfirst(str_replace('_', ' ', $e->event_type));

            if ($e->event_type === 'question_timed_out' && isset($meta['position'])) {
                $text = 'Question '.$meta['position'].' timed out';
            }

            return [
                'at' => $e->created_at,
                'who' => $name,
                'text' => $text,
                'detail' => isset($meta['reason']) ? 'Reason: '.str_replace('_', ' ', $meta['reason']) : null,
                'kind' => in_array($e->event_type, ['tab_hidden', 'fullscreen_exit'], true) ? 'flag' : 'candidate',
                'ip' => $e->ip_address,
            ];
        });

        return view('audit.registration', [
            'registration' => $registration,
            'name' => $name,
            'number' => $number,
            'timeline' => $items->concat($events)->sortBy(fn ($i) => $i['at']->getTimestamp() * 1000 + 0)->values(),
            'flags' => $events->where('kind', 'flag')->count(),
        ]);
    }
}
