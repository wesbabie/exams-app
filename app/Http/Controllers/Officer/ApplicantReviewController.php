<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\ExamRegistration;
use App\Models\Intake;
use App\Models\RegistrationLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApplicantReviewController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'submitted');
        $term = trim((string) $request->query('q'));

        $applicants = Applicant::with(['category', 'intake'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->filled('intake'), fn ($q) => $q->where('intake_id', $request->integer('intake')))
            ->when($request->filled('discipline'), fn ($q) => $q->where('exam_category_id', $request->integer('discipline')))
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('surname', 'like', "%{$term}%")
                ->orWhere('first_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('application_number', 'like', '%'.strtoupper($term).'%')))
            ->orderBy('submitted_at')->orderBy('id')
            ->paginate(25)->withQueryString();

        return view('officer.applicants.index', [
            'applicants' => $applicants,
            'status' => $status,
            'term' => $term,
            'intakes' => Intake::orderByDesc('opens_at')->get(),
            'disciplines' => ExamCategory::where('kind', 'intern_discipline')->orderBy('name')->get(),
            'counts' => Applicant::select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function show(Applicant $applicant): View
    {
        $applicant->load(['category', 'intake', 'reviewer']);

        $registration = $applicant->registrations()->with(['exam', 'examSession'])->latest('id')->first();

        return view('officer.applicants.show', [
            'applicant' => $applicant,
            'registration' => $registration,
            'exam' => $this->examFor($applicant),
        ]);
    }

    public function document(Applicant $applicant, string $kind): BinaryFileResponse
    {
        abort_unless(in_array($kind, ['passport', 'credential'], true), 404);

        $path = $applicant->{$kind.'_path'};
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path));
    }

    /**
     * Approve the documents: lock the discipline and put the applicant on the exam's candidate list.
     * An exam for the discipline and intake year must already exist.
     */
    public function approve(Request $request, Applicant $applicant): RedirectResponse
    {
        if ($applicant->status !== 'submitted') {
            return $this->back($applicant, 'error', 'Only a submitted application can be approved.');
        }

        $exam = $this->examFor($applicant);

        if (! $exam) {
            return $this->back($applicant, 'error', 'There is no open exam for '.$applicant->category?->name.' in '.$applicant->intake?->year.' yet. Create the exam first, then approve.');
        }

        DB::transaction(function () use ($applicant, $exam, $request) {
            $registration = ExamRegistration::firstOrCreate(
                ['exam_id' => $exam->id, 'candidate_type' => 'applicant', 'candidate_id' => $applicant->id],
                ['status' => 'approved'],
            );

            $applicant->forceFill([
                'status' => 'approved',
                'documents_verified_at' => now(),
                'documents_verified_by' => $request->user()->id,
                'discipline_locked_at' => now(),
                'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id,
                'review_note' => null,
            ])->save();

            RegistrationLog::record($exam->id, 'applicant_approved', $applicant->application_number, null, $registration->id);
        });

        return $this->back($applicant, 'status', 'Approved and added to the '.$exam->title.' candidate list.');
    }

    public function reject(Request $request, Applicant $applicant): RedirectResponse
    {
        return $this->decide($request, $applicant, 'rejected', 'Application rejected.');
    }

    public function returnForCorrection(Request $request, Applicant $applicant): RedirectResponse
    {
        if ($applicant->intake && ! $applicant->intake->isOpen()) {
            return $this->back($applicant, 'error', 'The registration window has closed, so the applicant cannot make corrections. Extend the closing date first.');
        }

        return $this->decide($request, $applicant, 'returned', 'Returned to the applicant for correction.');
    }

    private function decide(Request $request, Applicant $applicant, string $status, string $message): RedirectResponse
    {
        if ($applicant->status !== 'submitted') {
            return $this->back($applicant, 'error', 'Only a submitted application can be reviewed.');
        }

        $data = $request->validate(['note' => ['required', 'string', 'max:1000']], [
            'note.required' => 'Write the reason. The applicant will see it.',
        ]);

        $applicant->forceFill([
            'status' => $status,
            'review_note' => $data['note'],
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ])->save();

        return $this->back($applicant, 'status', $message);
    }

    /** The exam this applicant sits: their discipline, in the intake's year. */
    private function examFor(Applicant $applicant): ?Exam
    {
        if (! $applicant->exam_category_id || ! $applicant->intake) {
            return null;
        }

        return Exam::where('exam_category_id', $applicant->exam_category_id)
            ->where('cycle_year', $applicant->intake->year)
            ->where('status', '!=', 'closed')
            ->latest('id')
            ->first();
    }

    private function back(Applicant $applicant, string $key, string $message): RedirectResponse
    {
        return redirect()->route('officer.applicants.show', $applicant)->with($key, $message);
    }
}
