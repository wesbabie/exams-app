<?php

namespace App\Http\Controllers\Intern;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ExamCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        $applicant = $request->user('applicant')->load('intake');

        if (! $applicant->canEditProfile()) {
            return redirect()->route('intern.dashboard');
        }

        return view('intern.profile', ['applicant' => $applicant, 'disciplines' => $this->disciplines()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $applicant = $this->editable($request);

        $this->save($request, $applicant);

        return redirect()->route('intern.profile.edit')->with('status', 'Saved. Submit your application when everything is complete.');
    }

    public function submit(Request $request): RedirectResponse
    {
        $applicant = $this->editable($request);

        $this->save($request, $applicant);
        $applicant->refresh();

        if (! $applicant->profileIsComplete()) {
            return redirect()->route('intern.profile.edit')
                ->with('error', 'Choose your discipline and upload your passport photo and credential before submitting.');
        }

        $applicant->forceFill(['status' => 'submitted', 'submitted_at' => now()])->save();

        return redirect()->route('intern.dashboard')->with('status', 'Your application was submitted. We will review your documents.');
    }

    private function editable(Request $request): Applicant
    {
        $applicant = $request->user('applicant')->load('intake');

        abort_unless($applicant->canEditProfile(), 403, 'Your application can no longer be changed.');

        return $applicant;
    }

    private function save(Request $request, Applicant $applicant): void
    {
        $request->merge([
            'phone' => PhoneNumber::normalize((string) $request->input('phone')),
            'professional_reg_no' => strtoupper(trim((string) $request->input('professional_reg_no'))) ?: null,
        ]);

        $data = $request->validate([
            'surname' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'other_names' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'regex:'.PhoneNumber::PATTERN, Rule::unique('applicants', 'phone')->ignore($applicant->id)],
            'professional_reg_no' => ['nullable', 'string', 'max:50', Rule::unique('applicants', 'professional_reg_no')->ignore($applicant->id)],
            'exam_category_id' => ['required', Rule::in($this->disciplines()->pluck('id')->all())],
            'passport' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'credential' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'phone.unique' => 'Another application already uses this phone number.',
            'phone.regex' => 'Enter a valid phone number, for example 08012345678.',
            'professional_reg_no.unique' => 'Another application already uses this registration number.',
            'exam_category_id.required' => 'Choose your discipline.',
            'passport.max' => 'The passport photo must be 2 MB or smaller.',
            'credential.max' => 'The credential must be 5 MB or smaller.',
        ]);

        $fields = collect($data)->except(['passport', 'credential'])->all();

        if ($request->hasFile('passport')) {
            $fields['passport_path'] = $this->store($applicant, $request->file('passport'), 'passport', $applicant->passport_path);
        }
        if ($request->hasFile('credential')) {
            $fields['credential_path'] = $this->store($applicant, $request->file('credential'), 'credential', $applicant->credential_path);
        }

        $applicant->update($fields);
    }

    /** Files go on the private disk and are only served through the portal. */
    private function store(Applicant $applicant, UploadedFile $file, string $kind, ?string $old): string
    {
        if ($old) {
            Storage::disk('local')->delete($old);
        }

        return $file->storeAs(
            'applicants/'.$applicant->id,
            $kind.'-'.now()->timestamp.'.'.$file->getClientOriginalExtension(),
            'local',
        );
    }

    /** @return \Illuminate\Support\Collection<int, ExamCategory> */
    private function disciplines()
    {
        return ExamCategory::where('kind', 'intern_discipline')->where('is_active', true)->orderBy('name')->get();
    }
}
