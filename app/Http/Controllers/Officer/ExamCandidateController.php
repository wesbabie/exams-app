<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\EligibleList;
use App\Models\Exam;
use App\Models\ExamRegistration;
use App\Models\ExamSession;
use App\Models\RegistrationLog;
use App\Models\StaffCandidate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamCandidateController extends Controller
{
    private const MAX_ROWS = 2000;

    public function index(Request $request, Exam $exam): View
    {
        $exam->load('category');

        $status = $request->query('status', 'all');

        $registrations = ExamRegistration::where('exam_id', $exam->id)
            ->with('examSession')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->filled('sitting'), fn ($q) => $q->where('exam_session_id', $request->query('sitting')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->query('q').'%';
                $ids = StaffCandidate::where(fn ($w) => $w
                    ->where('file_number', 'like', strtoupper($term))
                    ->orWhere('surname', 'like', $term)
                    ->orWhere('first_name', 'like', $term))->pluck('id');
                $q->where('candidate_type', 'staff')->whereIn('candidate_id', $ids);
            })
            ->orderBy('id')
            ->paginate(30)
            ->withQueryString();

        $sittings = $exam->sessions()
            ->withCount(['registrations as assigned_count' => fn ($q) => $q->where('status', '!=', 'removed')])
            ->orderBy('starts_at')
            ->get();

        return view('officer.exams.candidates', [
            'exam' => $exam,
            'registrations' => $registrations,
            'cands' => $this->candidateMap($registrations->getCollection()),
            'status' => $status,
            'counts' => ExamRegistration::where('exam_id', $exam->id)->select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status'),
            'sittings' => $sittings,
            'lists' => EligibleList::where('exam_id', $exam->id)->with(['importer', 'approver'])
                ->withCount(['registrations as active_count' => fn ($q) => $q->where('status', '!=', 'removed')])
                ->latest('id')->get(),
            'log' => RegistrationLog::where('exam_id', $exam->id)->with('user')->latest('id')->limit(15)->get(),
            'isStaffExam' => $exam->category->kind === 'staff_cadre',
        ]);
    }

    public function template(Exam $exam): StreamedResponse
    {
        $category = $exam->load('category')->category->name;

        $rows = [
            ['file_number', 'surname', 'first_name', 'other_names', 'cadre', 'level', 'department', 'phone', 'email'],
            ['FN/0001', 'Sample', 'Candidate', '', $category, '', 'Nursing Services', '08030000001', 'sample1@example.com'],
            ['FN/0002', 'Example', 'Person', '', $category, '', 'Pharmacy', '', ''],
        ];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row, ',', '"', '');
            }
            fclose($out);
        }, 'eligible-list-template.csv', ['Content-Type' => 'text/csv']);
    }

    // ---- Importing and approving the list ----

    public function import(Request $request, Exam $exam): RedirectResponse
    {
        $exam->load('category');

        if ($blocked = $this->requireOpen($exam)) {
            return $blocked;
        }
        if ($exam->category->kind !== 'staff_cadre') {
            return $this->back($exam, 'error', 'List import is for staff exams. Interns register themselves.');
        }

        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        [$rows, $errors] = $this->parse($request->file('file')->getRealPath(), $exam);

        if ($errors) {
            return redirect()->route('officer.exams.candidates.index', $exam)
                ->with('error', 'Nothing was imported. Fix the rows listed below and upload the file again.')
                ->with('import_errors', array_slice($errors, 0, 50))
                ->with('import_error_total', count($errors));
        }

        DB::transaction(function () use ($rows, $exam, $request) {
            $list = EligibleList::create([
                'exam_id' => $exam->id,
                'original_filename' => $request->file('file')->getClientOriginalName(),
                'row_count' => count($rows),
                'imported_by' => $request->user()->id,
            ]);

            foreach ($rows as $row) {
                $candidate = StaffCandidate::updateOrCreate(
                    ['file_number' => $row['file_number']],
                    collect($row)->except('file_number')->all(),
                );
                $this->register($exam, $candidate, 'registered', $list->id);
            }

            RegistrationLog::record($exam->id, 'list_imported', count($rows).' candidates from '.$list->original_filename, null, null, $list->id);
        });

        return $this->back($exam, 'status', count($rows).' candidates imported. The list needs approval from another exam officer.');
    }

    public function approveList(Request $request, Exam $exam, EligibleList $list): RedirectResponse
    {
        abort_unless($list->exam_id === $exam->id, 404);

        if ($list->isApproved()) {
            return $this->back($exam, 'error', 'This list is already approved.');
        }
        // Two-person rule
        if ($list->imported_by === $request->user()->id) {
            return $this->back($exam, 'error', 'You imported this list, so someone else needs to approve it.');
        }

        DB::transaction(function () use ($list, $exam, $request) {
            $list->update(['approved_by' => $request->user()->id, 'approved_at' => now(), 'locked_at' => now()]);
            $list->registrations()->where('status', 'registered')->update(['status' => 'approved']);
            RegistrationLog::record($exam->id, 'list_approved', $list->original_filename, null, null, $list->id);
        });

        return $this->back($exam, 'status', 'List approved and locked. You can now assign these candidates to sittings.');
    }

    public function discardList(Exam $exam, EligibleList $list): RedirectResponse
    {
        abort_unless($list->exam_id === $exam->id, 404);

        if ($list->isApproved()) {
            return $this->back($exam, 'error', 'An approved list is locked. Remove candidates one at a time, with a reason.');
        }

        DB::transaction(function () use ($list, $exam) {
            $list->registrations()->delete();
            RegistrationLog::record($exam->id, 'list_discarded', $list->original_filename, null, null, $list->id);
            $list->delete();
        });

        return $this->back($exam, 'status', 'List discarded.');
    }

    // ---- Late additions and removals (a reason is required and logged) ----

    public function add(Request $request, Exam $exam): RedirectResponse
    {
        $exam->load('category');

        if ($blocked = $this->requireOpen($exam)) {
            return $blocked;
        }
        if ($exam->category->kind !== 'staff_cadre') {
            return $this->back($exam, 'error', 'Adding by file number is for staff exams.');
        }

        $request->merge(['file_number' => strtoupper(trim((string) $request->input('file_number')))]);

        $data = $request->validate([
            'file_number' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9][A-Z0-9\/\-\. ]*$/'],
            'surname' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'other_names' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'reason' => ['required', 'string', 'max:500'],
        ], ['reason.required' => 'Say why this candidate is being added.', 'file_number.regex' => 'That file number has characters that are not allowed.']);

        $existing = StaffCandidate::where('file_number', $data['file_number'])->first();
        if ($existing && ExamRegistration::where('exam_id', $exam->id)->where('candidate_type', 'staff')
            ->where('candidate_id', $existing->id)->where('status', '!=', 'removed')->exists()) {
            return $this->back($exam, 'error', "{$data['file_number']} is already on this exam's list.");
        }

        DB::transaction(function () use ($data, $exam) {
            $candidate = StaffCandidate::updateOrCreate(
                ['file_number' => $data['file_number']],
                collect($data)->except(['file_number', 'reason'])->filter(fn ($v) => $v !== null && $v !== '')->all(),
            );
            $registration = $this->register($exam, $candidate, 'approved', null);
            RegistrationLog::record($exam->id, 'candidate_added', $candidate->file_number, $data['reason'], $registration->id);
        });

        return $this->back($exam, 'status', "{$data['file_number']} added.");
    }

    public function remove(Request $request, Exam $exam, ExamRegistration $registration): RedirectResponse
    {
        abort_unless($registration->exam_id === $exam->id, 404);

        if ($blocked = $this->requireOpen($exam)) {
            return $blocked;
        }

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], ['reason.required' => 'Say why this candidate is being removed.']);

        if (! in_array($registration->status, ['registered', 'approved', 'assigned'], true)) {
            return $this->back($exam, 'error', 'This candidate has already been checked in or has no active registration, so they cannot be removed here.');
        }

        $label = $this->label($registration);
        $registration->update(['status' => 'removed', 'exam_session_id' => null, 'seat_number' => null]);
        RegistrationLog::record($exam->id, 'candidate_removed', $label, $data['reason'], $registration->id);

        return $this->back($exam, 'status', "{$label} removed.");
    }

    // ---- Sittings and seats ----

    public function assign(Request $request, Exam $exam, ExamRegistration $registration): RedirectResponse
    {
        abort_unless($registration->exam_id === $exam->id, 404);

        if ($blocked = $this->requireOpen($exam)) {
            return $blocked;
        }

        $data = $request->validate(['sitting_id' => ['required', 'integer']]);
        $sitting = ExamSession::where('exam_id', $exam->id)->find($data['sitting_id']);

        if (! $sitting || $sitting->status === 'closed') {
            return $this->back($exam, 'error', 'Choose an open sitting for this exam.');
        }
        if (! in_array($registration->status, ['approved', 'assigned'], true)) {
            return $this->back($exam, 'error', 'Only approved candidates can be assigned to a sitting.');
        }
        if ($registration->exam_session_id === $sitting->id) {
            return $this->back($exam, 'status', 'Already in that sitting.');
        }

        $seat = $this->nextFreeSeat($sitting);
        if ($seat === null) {
            return $this->back($exam, 'error', "{$sitting->name} is full ({$sitting->capacity} seats).");
        }

        $registration->update(['exam_session_id' => $sitting->id, 'seat_number' => (string) $seat, 'status' => 'assigned']);
        RegistrationLog::record($exam->id, 'assigned', $this->label($registration).' → '.$sitting->name.', seat '.$seat, null, $registration->id);

        return $this->back($exam, 'status', $this->label($registration)." assigned to {$sitting->name}, seat {$seat}.");
    }

    public function unassign(Exam $exam, ExamRegistration $registration): RedirectResponse
    {
        abort_unless($registration->exam_id === $exam->id, 404);

        if ($registration->status !== 'assigned') {
            return $this->back($exam, 'error', 'This candidate is not assigned to a sitting.');
        }

        $registration->update(['exam_session_id' => null, 'seat_number' => null, 'status' => 'approved']);
        RegistrationLog::record($exam->id, 'unassigned', $this->label($registration), null, $registration->id);

        return $this->back($exam, 'status', 'Removed from the sitting.');
    }

    /** Fill the sittings in date order with every approved candidate who has no sitting yet. */
    public function autoAssign(Exam $exam): RedirectResponse
    {
        if ($blocked = $this->requireOpen($exam)) {
            return $blocked;
        }

        $pending = ExamRegistration::where('exam_id', $exam->id)->where('status', 'approved')->get();
        if ($pending->isEmpty()) {
            return $this->back($exam, 'error', 'Nobody is waiting for a sitting.');
        }

        // Order by file number so seating is predictable
        $cands = $this->candidateMap($pending);
        $pending = $pending->sortBy(fn ($r) => $cands[$r->candidate_type][$r->candidate_id]?->file_number ?? $cands[$r->candidate_type][$r->candidate_id]?->application_number ?? '')->values();

        $sittings = $exam->sessions()->where('status', '!=', 'closed')->orderBy('starts_at')->get();
        $placed = 0;

        DB::transaction(function () use ($pending, $sittings, &$placed) {
            $queue = $pending->all();

            foreach ($sittings as $sitting) {
                while ($queue && ($seat = $this->nextFreeSeat($sitting)) !== null) {
                    $registration = array_shift($queue);
                    $registration->update(['exam_session_id' => $sitting->id, 'seat_number' => (string) $seat, 'status' => 'assigned']);
                    $placed++;
                }
            }
        });

        $left = $pending->count() - $placed;
        RegistrationLog::record($exam->id, 'auto_assigned', "{$placed} candidate(s) placed");

        return $this->back($exam, $left ? 'error' : 'status', $left
            ? "{$placed} candidate(s) placed. {$left} could not be placed because the sittings are full. Add a sitting or raise a capacity."
            : "{$placed} candidate(s) placed in sittings.");
    }

    /** Extra time for a candidate who needs it. Applies to every question's timer. */
    public function extraTime(Request $request, Exam $exam, ExamRegistration $registration): RedirectResponse
    {
        abort_unless($registration->exam_id === $exam->id, 404);

        if ($blocked = $this->requireOpen($exam)) {
            return $blocked;
        }

        $data = $request->validate([
            'multiplier' => ['required', Rule::in(['1.00', '1.25', '1.50', '2.00'])],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (in_array($registration->status, ['in_progress', 'completed'], true)) {
            return $this->back($exam, 'error', 'This candidate has already started, so their timers are fixed.');
        }
        if ((float) $data['multiplier'] > 1 && blank($data['reason'] ?? null)) {
            return $this->back($exam, 'error', 'Say why this candidate needs extra time.');
        }

        $registration->update(['extra_time_multiplier' => $data['multiplier']]);
        RegistrationLog::record($exam->id, 'extra_time_set', $this->label($registration).' ×'.(float) $data['multiplier'], $data['reason'] ?? null, $registration->id);

        return $this->back($exam, 'status', 'Extra time saved for '.$this->label($registration).'.');
    }

    // ---- Helpers ----

    /** Create the registration, or bring back one that was removed earlier. */
    private function register(Exam $exam, StaffCandidate $candidate, string $status, ?int $listId): ExamRegistration
    {
        $registration = ExamRegistration::where('exam_id', $exam->id)
            ->where('candidate_type', 'staff')->where('candidate_id', $candidate->id)->first();

        $fields = [
            'status' => $status, 'eligible_list_id' => $listId,
            'exam_session_id' => null, 'seat_number' => null,
        ];

        if ($registration) {
            $registration->update($fields);

            return $registration;
        }

        return ExamRegistration::create($fields + [
            'exam_id' => $exam->id, 'candidate_type' => 'staff', 'candidate_id' => $candidate->id,
        ]);
    }

    private function nextFreeSeat(ExamSession $sitting): ?int
    {
        $used = $sitting->registrations()->where('status', '!=', 'removed')->pluck('seat_number')->map(fn ($s) => (int) $s)->all();

        for ($seat = 1; $seat <= $sitting->capacity; $seat++) {
            if (! in_array($seat, $used, true)) {
                return $seat;
            }
        }

        return null;
    }

    private function candidateMap($registrations): array
    {
        return [
            'staff' => StaffCandidate::whereIn('id', $registrations->where('candidate_type', 'staff')->pluck('candidate_id'))->get()->keyBy('id'),
            'applicant' => Applicant::whereIn('id', $registrations->where('candidate_type', 'applicant')->pluck('candidate_id'))->get()->keyBy('id'),
        ];
    }

    private function label(ExamRegistration $registration): string
    {
        $candidate = $registration->candidate;

        return $candidate->file_number ?? $candidate->application_number ?? ('#'.$registration->id);
    }

    private function requireOpen(Exam $exam): ?RedirectResponse
    {
        return $exam->status === 'closed' ? $this->back($exam, 'error', 'This exam is closed.') : null;
    }

    private function back(Exam $exam, string $key, string $message): RedirectResponse
    {
        return redirect()->route('officer.exams.candidates.index', $exam)->with($key, $message);
    }

    private function norm(string $value): string
    {
        return preg_replace('/\s+/', ' ', mb_strtolower(trim($value)));
    }

    /** @return array{0: array, 1: string[]}  [valid rows, errors] */
    private function parse(string $path, Exam $exam): array
    {
        $errors = [];
        $rows = [];

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, null, ',', '"', '');

        if (! $header) {
            return [[], ['The file is empty.']];
        }

        $header = array_map(fn ($h) => strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $h))), $header);

        foreach (['file_number', 'surname', 'first_name'] as $required) {
            if (! in_array($required, $header, true)) {
                return [[], ["The file needs a \"{$required}\" column. Download the template to see the columns."]];
            }
        }

        $expected = $this->norm($exam->category->name);

        // File numbers already on this exam's list
        $activeIds = ExamRegistration::where('exam_id', $exam->id)->where('candidate_type', 'staff')
            ->where('status', '!=', 'removed')->pluck('candidate_id');
        $onList = StaffCandidate::whereIn('id', $activeIds)->pluck('file_number')->flip();

        $seen = [];
        $line = 1;

        while (($cells = fgetcsv($handle, null, ',', '"', '')) !== false) {
            $line++;

            if (count(array_filter($cells, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }
            if ($line > self::MAX_ROWS + 1) {
                $errors[] = 'The file has more than '.self::MAX_ROWS.' rows. Split it into smaller files.';
                break;
            }

            $cells = array_slice(array_pad($cells, count($header), ''), 0, count($header));
            $row = array_combine($header, array_map('trim', $cells));
            $rowErrors = [];

            $number = strtoupper($row['file_number']);

            if ($number === '' || mb_strlen($number) > 30 || ! preg_match('/^[A-Z0-9][A-Z0-9\/\-\. ]*$/', $number)) {
                $rowErrors[] = 'the file number is missing or has characters that are not allowed';
            } elseif (isset($seen[$number])) {
                $rowErrors[] = "the file number {$number} appears twice in this file (first on row {$seen[$number]})";
            } elseif ($onList->has($number)) {
                $rowErrors[] = "{$number} is already on this exam's list";
            }
            $seen[$number] ??= $line;

            if ($row['surname'] === '' || $row['first_name'] === '') {
                $rowErrors[] = 'the surname or first name is missing';
            }

            $given = $this->norm(($row['cadre'] ?? '').' '.($row['level'] ?? ''));
            if ($given !== '' && $given !== $expected) {
                $rowErrors[] = 'the cadre and level ("'.trim(($row['cadre'] ?? '').' '.($row['level'] ?? '')).'") do not match this exam\'s category ("'.$exam->category->name.'")';
            }

            if (($row['email'] ?? '') !== '' && ! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = 'the email address is not valid';
            }
            if (mb_strlen($row['phone'] ?? '') > 30) {
                $rowErrors[] = 'the phone number is too long';
            }

            if ($rowErrors) {
                foreach ($rowErrors as $message) {
                    $errors[] = "Row {$line}: {$message}.";
                }
                continue;
            }

            $rows[] = [
                'file_number' => $number,
                'surname' => $row['surname'],
                'first_name' => $row['first_name'],
                'other_names' => ($row['other_names'] ?? '') ?: null,
                'cadre' => ($row['cadre'] ?? '') ?: null,
                'level' => ($row['level'] ?? '') ?: null,
                'department' => ($row['department'] ?? '') ?: null,
                'phone' => ($row['phone'] ?? '') ?: null,
                'email' => ($row['email'] ?? '') ?: null,
            ];
        }

        fclose($handle);

        if (! $rows && ! $errors) {
            $errors[] = 'The file has no candidates in it.';
        }

        return [$rows, $errors];
    }
}
