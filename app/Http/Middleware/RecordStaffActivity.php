<?php

namespace App\Http\Middleware;

use App\Models\Applicant;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\ExamPaper;
use App\Models\ExamSection;
use App\Models\ExamSession;
use App\Models\Intake;
use App\Models\Question;
use App\Models\SectionTopicBlueprint;
use App\Models\Topic;
use App\Support\Audit;
use App\Support\AuditCatalog;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records what staff change. For each successful action on a known route it writes one audit line with
 * the person, the thing they changed and, for edits, the old and new values.
 */
class RecordStaffActivity
{
    /** Models whose changes are worth showing. Passwords and PIN hashes never appear. */
    private const TRACKED = [
        Exam::class, ExamSection::class, ExamSession::class, ExamPaper::class, Question::class, Topic::class,
        ExamCategory::class, Intake::class, Applicant::class, SectionTopicBlueprint::class,
    ];

    private const IGNORED_ATTRIBUTES = ['updated_at', 'created_at', 'password', 'remember_token', 'pin_hash', 'email_otp_hash', 'email_otp_expires_at', 'email_otp_sent_at', 'email_otp_attempts'];

    public function handle(Request $request, Closure $next): Response
    {
        $name = $request->route()?->getName();

        // Only staff actions on routes in the catalog; reading a page is not an action (exports are the exception).
        if (! $request->user() || ! AuditCatalog::recordsRoute($name) || (! $this->isWrite($request) && ! str_contains($name, 'export') && ! str_contains($name, 'print'))) {
            return $next($request);
        }

        $changes = [];
        $handler = function (string $event, array $payload) use (&$changes) {
            $model = $payload[0] ?? null;

            if ($model instanceof Model && in_array($model::class, self::TRACKED, true)) {
                $this->collect($event, $model, $changes);
            }
        };

        foreach (['eloquent.updating: *', 'eloquent.created: *', 'eloquent.deleted: *'] as $pattern) {
            Event::listen($pattern, $handler);
        }

        try {
            $response = $next($request);
        } finally {
            foreach (['eloquent.updating: *', 'eloquent.created: *', 'eloquent.deleted: *'] as $pattern) {
                Event::forget($pattern);
            }
        }

        if ($this->succeeded($request, $response)) {
            $this->write($request, $name, $changes);
        }

        return $response;
    }

    private function isWrite(Request $request): bool
    {
        return ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
    }

    /** A refused action (validation errors or an "error" notice) changed nothing, so it is not recorded. */
    private function succeeded(Request $request, Response $response): bool
    {
        if ($response->getStatusCode() >= 400) {
            return false;
        }

        if ($request->hasSession()) {
            $session = $request->session();

            if ($session->get('error') || ($session->get('errors')?->any() ?? false)) {
                return false;
            }
        }

        return true;
    }

    private function collect(string $event, Model $model, array &$changes): void
    {
        $entry = ['model' => class_basename($model), 'id' => $model->getKey(), 'label' => $this->label($model)];

        if (str_starts_with($event, 'eloquent.updating')) {
            $diff = [];

            foreach ($model->getDirty() as $key => $new) {
                if (! in_array($key, self::IGNORED_ATTRIBUTES, true)) {
                    $diff[$key] = ['from' => $model->getOriginal($key), 'to' => $new];
                }
            }

            if (! $diff) {
                return;
            }

            $entry['changed'] = $diff;
        } elseif (str_starts_with($event, 'eloquent.created')) {
            $entry['created'] = true;
        } else {
            $entry['deleted'] = true;
        }

        $key = $entry['model'].':'.$entry['id'].':'.(isset($entry['changed']) ? 'u' : (isset($entry['created']) ? 'c' : 'd'));

        // A record saved twice in one request (for example while renumbering) keeps its first old value and last new value.
        if (isset($entry['changed']) && isset($changes[$key])) {
            foreach ($entry['changed'] as $field => $diff) {
                $changes[$key]['changed'][$field] = [
                    'from' => $changes[$key]['changed'][$field]['from'] ?? $diff['from'],
                    'to' => $diff['to'],
                ];
            }

            return;
        }

        if (count($changes) < 40) {
            $changes[$key] = $entry;
        }
    }

    /** Drop fields that ended up where they started, and records with nothing left to show. */
    private function tidy(array $changes): array
    {
        $out = [];

        foreach ($changes as $entry) {
            // Saving an exam moves section positions up by 100 and back, so work out the real starting position.
            if ($entry['model'] === 'ExamSection' && isset($entry['changed']['position'])) {
                $from = $entry['changed']['position']['from'] ?? null;

                if (is_numeric($from) && $from >= 100) {
                    $entry['changed']['position']['from'] = $from - 100;
                }
            }

            if (isset($entry['changed'])) {
                $entry['changed'] = array_filter($entry['changed'], fn ($d) => ! $this->same($d['from'] ?? null, $d['to'] ?? null));

                if (! $entry['changed']) {
                    continue;
                }
            }

            $out[] = $entry;
        }

        return $out;
    }

    private function same(mixed $a, mixed $b): bool
    {
        if (is_numeric($a) && is_numeric($b)) {
            return (float) $a === (float) $b;
        }

        return (string) $a === (string) $b;
    }

    private function write(Request $request, string $name, array $changes): void
    {
        [$category, $summary] = AuditCatalog::route($name);

        $changes = $this->tidy($changes);
        [$subject, $examId] = $this->subject($request, $changes);

        $meta = array_filter([
            'changes' => $changes ?: null,
            'count' => is_array($request->input('ids')) ? count($request->input('ids')) : null,
        ]);

        // Saving a form without changing anything is still worth a line, but say so plainly.
        $note = (! $changes && str_ends_with($name, '.update')) ? ' (nothing changed)' : '';

        Audit::record($name, $category, $summary.($subject ? ' · '.$subject['label'] : '').$note, [
            'subject_type' => $subject['type'] ?? null,
            'subject_id' => $subject['id'] ?? null,
            'subject_label' => $subject['label'] ?? null,
            'exam_id' => $examId,
            'reason' => $request->filled('reason') ? (string) $request->input('reason') : ($request->filled('note') ? (string) $request->input('note') : null),
            'meta' => $meta ?: null,
        ]);
    }

    /** @return array{0: ?array{type: string, id: mixed, label: string}, 1: ?int} */
    private function subject(Request $request, array $changes): array
    {
        $params = collect($request->route()->parameters())->filter(fn ($v) => $v instanceof Model);

        // Most specific thing first
        foreach (['question', 'sitting', 'section', 'list', 'applicant', 'intake', 'topic', 'category', 'exam'] as $key) {
            $model = $params->get($key);

            if ($model) {
                $examId = $params->get('exam')?->getKey() ?? ($model instanceof Exam ? $model->getKey() : ($model->exam_id ?? null));

                return [['type' => $key, 'id' => $model->getKey(), 'label' => $this->label($model)], $examId];
            }
        }

        // A create has no bound model yet: use the first thing that was created.
        foreach ($changes as $c) {
            if (! empty($c['created'])) {
                return [['type' => Str::snake($c['model']), 'id' => $c['id'], 'label' => $c['label']], $c['model'] === 'Exam' ? $c['id'] : null];
            }
        }

        return [null, null];
    }

    private function label(Model $model): string
    {
        return match (true) {
            $model instanceof Exam => $model->title,
            $model instanceof Question => '#'.$model->getKey().' '.Str::limit((string) $model->stem, 60),
            $model instanceof ExamSession => $model->name,
            $model instanceof ExamSection => $model->title,
            $model instanceof Topic, $model instanceof ExamCategory, $model instanceof Intake => $model->name,
            $model instanceof Applicant => (string) $model->application_number,
            $model instanceof ExamPaper => 'Paper #'.$model->getKey(),
            $model instanceof SectionTopicBlueprint => 'Topic count #'.$model->getKey(),
            default => class_basename($model).' #'.$model->getKey(),
        };
    }
}
