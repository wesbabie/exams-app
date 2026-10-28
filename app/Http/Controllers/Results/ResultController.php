<?php

namespace App\Http\Controllers\Results;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Attempt;
use App\Models\AttemptEvent;
use App\Models\Exam;
use App\Models\ExamRegistration;
use App\Models\Question;
use App\Models\RegistrationLog;
use App\Models\Result;
use App\Models\StaffCandidate;
use App\Services\RankingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResultController extends Controller
{
    public function __construct(private RankingService $ranking)
    {
    }

    // ---- Screens ----

    /** Exams that have results, or are scheduled or closed. */
    public function index(): View
    {
        $withResults = Result::select('exam_id', DB::raw('count(*) as submitted'), DB::raw('sum(case when passed = 1 then 1 else 0 end) as passed'), DB::raw('avg(percentage) as average'))
            ->groupBy('exam_id')->get()->keyBy('exam_id');

        $exams = Exam::with('category')
            ->where(fn ($q) => $q->whereIn('id', $withResults->keys())->orWhereIn('status', ['scheduled', 'closed']))
            ->latest('updated_at')
            ->get();

        $registered = ExamRegistration::whereIn('exam_id', $exams->pluck('id'))->where('status', '!=', 'removed')
            ->select('exam_id', DB::raw('count(*) as c'))->groupBy('exam_id')->pluck('c', 'exam_id');

        return view('results.index', compact('exams', 'withResults', 'registered'));
    }

    public function show(Request $request, Exam $exam): View
    {
        $exam->load('category', 'sections');
        $rows = $this->rows($exam);

        // Filters
        $outcome = $request->query('outcome', 'all');
        $term = mb_strtolower(trim((string) $request->query('q')));

        $shown = $rows->filter(function ($row) use ($outcome, $term) {
            if ($outcome === 'pass' && ! $row['result']->passed) {
                return false;
            }
            if ($outcome === 'fail' && $row['result']->passed) {
                return false;
            }
            if ($outcome === 'shortlisted' && ! $row['shortlisted']) {
                return false;
            }
            if ($term !== '') {
                return str_contains(mb_strtolower($row['name'].' '.$row['number']), $term);
            }

            return true;
        })->values();

        $all = $rows->pluck('result');

        return view('results.show', [
            'exam' => $exam,
            'rows' => $shown,
            'outcome' => $outcome,
            'stats' => [
                'submitted' => $all->count(),
                'passed' => $all->where('passed', true)->count(),
                'average' => $all->count() ? round($all->avg('percentage'), 1) : null,
                'highest' => $all->count() ? (float) $all->max('percentage') : null,
            ],
            'registrationCounts' => ExamRegistration::where('exam_id', $exam->id)->select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status'),
            'sittingAverages' => $this->sittingAverages($rows),
            'tieSection' => $this->ranking->tieBreakSection($exam),
            'canManage' => in_array($request->user()->role, ['exam_officer', 'super_admin'], true),
            'tiesAtCutoff' => $exam->top_n && $rows->where('shortlisted', true)->count() > $exam->top_n,
        ]);
    }

    /** One candidate's attempt in detail, for appeals and checking. */
    public function attempt(Attempt $attempt): View
    {
        $attempt->load('registration.exam.sections', 'result.sections', 'events');
        $registration = $attempt->registration;
        $candidate = $registration->candidate;

        $questions = $attempt->questions()->with('paperQuestion.question.options', 'section')->get();

        return view('results.attempt', [
            'attempt' => $attempt,
            'exam' => $registration->exam,
            'registration' => $registration,
            'candidate' => $candidate,
            'result' => $attempt->result,
            'questions' => $questions,
            'events' => $attempt->events()->orderBy('id')->get(),
            'rank' => $attempt->result ? $this->ranking->ranked($registration->exam)->firstWhere(fn ($r) => $r['result']->id === $attempt->result->id)['rank'] ?? null : null,
        ]);
    }

    /** How each question performed: a quick check that the paper was fair and the answer keys are right. */
    public function analysis(Exam $exam): View
    {
        $exam->load('sections');

        $stats = DB::table('attempt_questions as aq')
            ->join('attempts as a', 'a.id', '=', 'aq.attempt_id')
            ->join('exam_registrations as r', 'r.id', '=', 'a.exam_registration_id')
            ->join('exam_paper_questions as pq', 'pq.id', '=', 'aq.exam_paper_question_id')
            ->where('r.exam_id', $exam->id)->where('a.status', 'submitted')
            ->select(
                'pq.question_id', 'pq.exam_section_id',
                DB::raw('count(*) as total'),
                DB::raw('sum(case when aq.is_correct = 1 then 1 else 0 end) as correct'),
                DB::raw('sum(case when aq.is_correct is null then 1 else 0 end) as unanswered'),
                DB::raw("sum(case when aq.status = 'timed_out' then 1 else 0 end) as timed_out")
            )
            ->groupBy('pq.question_id', 'pq.exam_section_id')
            ->get();

        $questions = Question::with('topic')->whereIn('id', $stats->pluck('question_id'))->get()->keyBy('id');

        $rows = $stats->map(fn ($s) => [
            'question' => $questions[$s->question_id] ?? null,
            'section' => $exam->sections->firstWhere('id', $s->exam_section_id),
            'total' => (int) $s->total,
            'correctPct' => $s->total ? round($s->correct / $s->total * 100) : 0,
            'unansweredPct' => $s->total ? round($s->unanswered / $s->total * 100) : 0,
            'timedOutPct' => $s->total ? round($s->timed_out / $s->total * 100) : 0,
        ])->filter(fn ($r) => $r['question'])->sortBy('correctPct')->values();

        return view('results.analysis', compact('exam', 'rows'));
    }

    // ---- Release ----

    public function release(Request $request, Exam $exam): RedirectResponse
    {
        if (! Result::where('exam_id', $exam->id)->exists()) {
            return $this->back($exam, 'error', 'There are no results to release yet.');
        }

        $writing = ExamRegistration::where('exam_id', $exam->id)->where('status', 'in_progress')->count();
        if ($writing > 0) {
            return $this->back($exam, 'error', "{$writing} candidate(s) are still writing. Wait until they finish, or run php artisan exams:finalize-attempts, then release.");
        }

        DB::transaction(function () use ($exam) {
            $this->ranking->persist($exam);
            $exam->update(['results_released_at' => now()]);
            Result::where('exam_id', $exam->id)->update(['released_at' => now()]);
            RegistrationLog::record($exam->id, 'results_released', 'Candidates see: '.$exam->result_release);
        });

        return $this->back($exam, 'status', 'Results released. Ranks and the shortlist are saved, and result slips are available.');
    }

    public function withdraw(Exam $exam): RedirectResponse
    {
        DB::transaction(function () use ($exam) {
            $exam->update(['results_released_at' => null]);
            Result::where('exam_id', $exam->id)->update(['released_at' => null]);
            RegistrationLog::record($exam->id, 'results_withdrawn');
        });

        return $this->back($exam, 'status', 'Release withdrawn. Results are hidden from candidates again.');
    }

    public function recalculate(Exam $exam): RedirectResponse
    {
        $this->ranking->persist($exam);

        return $this->back($exam, 'status', 'Ranks and the shortlist recalculated and saved.');
    }

    // ---- Exports and slips ----

    public function export(Exam $exam): StreamedResponse
    {
        $exam->load('sections');
        $rows = $this->rows($exam);

        $header = ['Rank', 'File / application no.', 'Surname', 'First name', 'Cadre', 'Department', 'Sitting'];
        foreach ($exam->sections as $s) {
            $header[] = $s->title.' (marks)';
        }
        array_push($header, 'Total marks', 'Out of', 'Percentage', 'Result', 'Shortlisted', 'Submitted at', 'Tab-switch flags');

        $filename = \Illuminate\Support\Str::slug($exam->title).'-results.csv';

        return response()->streamDownload(function () use ($rows, $exam, $header) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel reads accented names correctly
            fputcsv($out, $header, ',', '"', '');

            foreach ($rows as $row) {
                $r = $row['result'];
                $line = [$row['rank'], $row['number'], $row['candidate']->surname ?? '', $row['candidate']->first_name ?? '', trim(($row['candidate']->cadre ?? '').' '.($row['candidate']->level ?? '')), $row['candidate']->department ?? '', $row['sitting'] ?? ''];

                foreach ($exam->sections as $s) {
                    $line[] = (float) ($r->sections->firstWhere('exam_section_id', $s->id)?->marks ?? 0);
                }

                array_push($line, (float) $r->total_marks, (float) $r->max_marks, (float) $r->percentage, $r->passed ? 'Pass' : 'Fail', $row['shortlisted'] ? 'Yes' : '', $r->attempt->submitted_at?->format('Y-m-d H:i'), $row['flags']);
                fputcsv($out, $line, ',', '"', '');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function print(Exam $exam): View
    {
        $exam->load('category', 'sections');

        return view('results.print', ['exam' => $exam, 'rows' => $this->rows($exam)]);
    }

    public function slips(Exam $exam): View|RedirectResponse
    {
        return $this->slipPages($exam, $this->rows($exam));
    }

    public function slip(Result $result): View|RedirectResponse
    {
        $exam = Exam::with('sections')->findOrFail($result->exam_id);

        return $this->slipPages($exam, $this->rows($exam)->filter(fn ($r) => $r['result']->id === $result->id)->values());
    }

    // ---- Helpers ----

    private function slipPages(Exam $exam, $rows): View|RedirectResponse
    {
        if (! $exam->results_released_at) {
            return $this->back($exam, 'error', 'Release the results first. Slips are only available once results are released.');
        }
        if ($exam->result_release === 'hidden') {
            return $this->back($exam, 'error', 'This exam is set to show candidates nothing. Change "What candidates see" on the exam to pass/fail or full breakdown to print slips.');
        }

        return view('results.slips', ['exam' => $exam->load('sections'), 'rows' => $rows]);
    }

    /** Ranked results with the candidate's details, ready for the screens and exports. */
    private function rows(Exam $exam)
    {
        $ranked = $this->ranking->ranked($exam);
        $registrations = $ranked->map(fn ($row) => $row['result']->attempt->registration);

        $staff = StaffCandidate::whereIn('id', $registrations->where('candidate_type', 'staff')->pluck('candidate_id'))->get()->keyBy('id');
        $applicants = Applicant::whereIn('id', $registrations->where('candidate_type', 'applicant')->pluck('candidate_id'))->get()->keyBy('id');

        $flags = AttemptEvent::whereIn('attempt_id', $ranked->map(fn ($r) => $r['result']->attempt_id))
            ->where('event_type', 'tab_hidden')
            ->select('attempt_id', DB::raw('count(*) as c'))->groupBy('attempt_id')->pluck('c', 'attempt_id');

        return $ranked->map(function ($row) use ($staff, $applicants, $flags) {
            $registration = $row['result']->attempt->registration;
            $candidate = $registration->candidate_type === 'staff' ? $staff[$registration->candidate_id] ?? null : $applicants[$registration->candidate_id] ?? null;

            return $row + [
                'candidate' => $candidate ?? new StaffCandidate(),
                'registration' => $registration,
                'name' => $candidate?->full_name ?? '–',
                'number' => $candidate?->file_number ?? $candidate?->application_number ?? '',
                'sitting' => $registration->examSession?->name,
                'sittingId' => $registration->exam_session_id,
                'flags' => (int) ($flags[$row['result']->attempt_id] ?? 0),
            ];
        });
    }

    private function sittingAverages($rows): array
    {
        return $rows->groupBy('sittingId')->map(fn ($group) => [
            'name' => $group->first()['sitting'] ?? '–',
            'count' => $group->count(),
            'average' => round($group->avg(fn ($r) => (float) $r['result']->percentage), 1),
        ])->sortBy('name')->values()->all();
    }

    private function back(Exam $exam, string $key, string $message): RedirectResponse
    {
        return redirect()->route('results.exams.show', $exam)->with($key, $message);
    }
}
