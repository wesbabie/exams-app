<?php

namespace App\Enums;

enum StaffRole: string
{
    case SuperAdmin = 'super_admin';
    case ExamOfficer = 'exam_officer';
    case Author = 'question_author';
    case Reviewer = 'reviewer';
    case Invigilator = 'invigilator';
    case Committee = 'committee';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super admin',
            self::ExamOfficer => 'Exam officer',
            self::Author => 'Question author',
            self::Reviewer => 'Reviewer',
            self::Invigilator => 'Invigilator',
            self::Committee => 'Committee',
        };
    }

    /** Top navigation: [label, route name, route pattern for the active highlight]. Only built areas are listed. */
    public function navLinks(): array
    {
        $links = [['Dashboard', 'staff.dashboard', 'staff.dashboard']];

        if (in_array($this, [self::ExamOfficer, self::SuperAdmin], true)) {
            $links[] = ['Exams', 'officer.exams.index', 'officer.exams.*'];
            $links[] = ['Categories', 'officer.categories.index', 'officer.categories.*'];
            $links[] = ['Interns', 'officer.applicants.index', 'officer.applicants.*'];
            $links[] = ['Intake', 'officer.intakes.index', 'officer.intakes.*'];
        }

        if (in_array($this, [self::ExamOfficer, self::SuperAdmin, self::Author, self::Reviewer], true)) {
            $links[] = ['Questions', 'bank.questions.index', 'bank.questions.*'];
        }

        if (in_array($this, [self::ExamOfficer, self::SuperAdmin], true)) {
            $links[] = ['Topics', 'bank.topics.index', 'bank.topics.*'];
        }

        if (in_array($this, [self::ExamOfficer, self::SuperAdmin, self::Invigilator], true)) {
            $links[] = ['Check-in', 'invigilator.sittings.index', 'invigilator.*'];
        }

        if (in_array($this, [self::ExamOfficer, self::SuperAdmin, self::Committee], true)) {
            $links[] = ['Results', 'results.index', 'results.*'];
        }

        if ($this === self::SuperAdmin) {
            $links[] = ['Staff', 'admin.staff.index', 'admin.*'];
        }

        return $links;
    }

    /** Work areas for this role: [title, description, route name or null if not built yet]. */
    public function workAreas(): array
    {
        return match ($this) {
            self::SuperAdmin => [
                ['Exams and categories', 'Set up exams, sections, timers and categories.', 'officer.exams.index'],
                ['Question bank', 'Browse, review and manage all questions.', 'bank.questions.index'],
                ['Topics', 'Topics that questions are filed under.', 'bank.topics.index'],
                ['Results', 'Rankings, release and reports for every exam.', 'results.index'],
                ['Staff accounts and roles', 'Create staff accounts, assign roles, reset passwords, deactivate accounts.', 'admin.staff.index'],
                ['System settings', 'Settings that apply across every exam.', null],
                ['Audit log', 'Who changed what, and when.', null],
            ],
            self::ExamOfficer => [
                ['Exams and sections', 'Set up exams, sections, timers and the pass mark.', 'officer.exams.index'],
                ['Exam categories', 'Cadres and levels for staff, disciplines for interns.', 'officer.categories.index'],
                ['Question bank', 'Browse, review and manage questions.', 'bank.questions.index'],
                ['Topics', 'Topics that questions are filed under.', 'bank.topics.index'],
                ['Intern registration', 'Open and close intake windows, and review and approve intern applications.', 'officer.applicants.index'],
                ['Eligible lists', 'Open an exam, then Candidates: import the list from admin staff and get it approved.', 'officer.exams.index'],
                ['Sittings and seats', 'Open an exam, then Sittings: schedule sittings and assign candidates to seats.', 'officer.exams.index'],
                ['Exam-day check-in', 'Check candidates in, give out PINs, admit them and unlock accounts.', 'invigilator.sittings.index'],
                ['Results', 'Rankings, shortlists, release and reports for every exam.', 'results.index'],
            ],
            self::Author => [
                ['My questions', 'Write questions and send them for review.', 'bank.questions.index'],
                ['Import questions', 'Bring in questions from a CSV file.', 'bank.import.create'],
            ],
            self::Reviewer => [
                ['Questions to review', 'Approve or return questions written by authors.', 'bank.questions.index'],
                ['Papers to approve', 'Check a paper before it is frozen.', null],
            ],
            self::Invigilator => [
                ['Today’s sittings', 'The sittings happening today and in the coming week.', 'invigilator.sittings.index'],
                ['Check-in and admit', 'Confirm identity, give out PINs and admit candidates.', 'invigilator.sittings.index'],
                ['Unlock accounts', 'Unlock a candidate after too many wrong PINs.', 'invigilator.sittings.index'],
            ],
            self::Committee => [
                ['Results and rankings', 'Section scores, totals and ranks per exam.', 'results.index'],
                ['Reports', 'Export ranked results to Excel (CSV) or print them.', 'results.index'],
            ],
        };
    }
}
