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
        }

        return $links;
    }

    /** Work areas for this role: [title, description, route name or null if not built yet]. */
    public function workAreas(): array
    {
        return match ($this) {
            self::SuperAdmin => [
                ['Exams and categories', 'Set up exams, sections, timers and categories.', 'officer.exams.index'],
                ['Staff accounts and roles', 'Create staff accounts, assign roles, deactivate accounts.', null],
                ['System settings', 'Settings that apply across every exam.', null],
                ['Audit log', 'Who changed what, and when.', null],
            ],
            self::ExamOfficer => [
                ['Exams and sections', 'Set up exams, sections, timers and the pass mark.', 'officer.exams.index'],
                ['Exam categories', 'Cadres and levels for staff, disciplines for interns.', 'officer.categories.index'],
                ['Eligible lists', 'Import the list from admin staff and send it for approval.', null],
                ['Sittings and papers', 'Schedule sittings, assign candidates and seats, build the paper.', null],
                ['Results release', 'Decide when and how results are shown.', null],
            ],
            self::Author => [
                ['My questions', 'Write questions and submit them for review.', null],
                ['Import questions', 'Bring in questions from Excel or CSV.', null],
            ],
            self::Reviewer => [
                ['Questions to review', 'Approve or return questions written by authors.', null],
                ['Papers to approve', 'Check a paper before it is frozen.', null],
            ],
            self::Invigilator => [
                ['Today’s sittings', 'The sittings you are invigilating.', null],
                ['Check-in and admit', 'Confirm identity, give out PINs and admit candidates.', null],
                ['Unlock accounts', 'Unlock a candidate after too many wrong PINs.', null],
            ],
            self::Committee => [
                ['Results and rankings', 'Section scores, totals and ranks per exam.', null],
                ['Reports', 'Export results to Excel or PDF.', null],
            ],
        };
    }
}
