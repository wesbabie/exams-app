<?php

namespace App\Support;

/**
 * Plain-language names for everything the audit log records.
 * Kept in one place so the viewer, the recorder and the back-fill all agree.
 */
final class AuditCatalog
{
    public const CATEGORIES = [
        'exams' => 'Exams and setup',
        'sittings' => 'Sittings and papers',
        'questions' => 'Questions',
        'candidates' => 'Candidates and check-in',
        'results' => 'Results',
        'interns' => 'Interns',
        'accounts' => 'Accounts and sign-ins',
    ];

    /** Actions that deserve a second look when scanning the log. */
    public const SENSITIVE = [
        'results_released', 'results_withdrawn', 'results.exams.recalculate', 'results.exams.export',
        'account_unlocked', 'pin_reissued', 'admission_revoked', 'check_in_undone',
        'candidate_removed', 'list_discarded', 'extra_time_set',
        'staff.deactivated', 'staff.password_reset', 'auth.failed', 'candidate.locked', 'candidate.results_code_failed', 'audit.export',
        'bank.questions.destroy', 'officer.exams.reopen', 'officer.exams.sittings.destroy',
        'officer.applicants.reject', 'bank.questions.retire',
    ];

    /** Staff actions captured from the request: route name => [category, what happened]. */
    private const ROUTES = [
        'bank.import.store' => ['questions', 'Imported questions from a CSV file'],
        'bank.questions.store' => ['questions', 'Wrote a question'],
        'bank.questions.update' => ['questions', 'Edited a question'],
        'bank.questions.destroy' => ['questions', 'Deleted a draft question'],
        'bank.questions.submit' => ['questions', 'Sent a question for review'],
        'bank.questions.submit-all' => ['questions', 'Sent all drafts for review'],
        'bank.questions.withdraw' => ['questions', 'Took a question back to draft'],
        'bank.questions.approve' => ['questions', 'Approved a question'],
        'bank.questions.approve-selected' => ['questions', 'Approved selected questions'],
        'bank.questions.return' => ['questions', 'Returned a question to its author'],
        'bank.questions.retire' => ['questions', 'Retired a question'],
        'bank.questions.version' => ['questions', 'Created a new version of a question'],
        'bank.topics.store' => ['questions', 'Added a topic'],
        'bank.topics.update' => ['questions', 'Edited a topic'],

        'officer.categories.store' => ['exams', 'Added an exam category'],
        'officer.categories.update' => ['exams', 'Edited an exam category'],
        'officer.exams.store' => ['exams', 'Created an exam'],
        'officer.exams.update' => ['exams', 'Edited an exam'],
        'officer.exams.submit' => ['exams', 'Sent an exam for review'],
        'officer.exams.approve' => ['exams', 'Approved an exam'],
        'officer.exams.reopen' => ['exams', 'Returned an exam to draft'],
        'officer.exams.sections.questions.add' => ['exams', 'Added a question to a section'],
        'officer.exams.sections.questions.remove' => ['exams', 'Removed a question from a section'],
        'officer.exams.sections.questions.move' => ['exams', 'Reordered a section\'s questions'],
        'officer.exams.sections.blueprint.update' => ['exams', 'Changed the topic counts for a section'],

        'officer.exams.sittings.store' => ['sittings', 'Added a sitting'],
        'officer.exams.sittings.update' => ['sittings', 'Edited a sitting'],
        'officer.exams.sittings.destroy' => ['sittings', 'Deleted a sitting'],
        'officer.exams.sittings.close' => ['sittings', 'Closed a sitting'],
        'officer.exams.sittings.reopen' => ['sittings', 'Reopened a sitting'],
        'officer.exams.papers.generate' => ['sittings', 'Generated a paper'],
        'officer.exams.papers.generate-all' => ['sittings', 'Generated papers for all sittings'],
        'officer.exams.papers.freeze' => ['sittings', 'Froze a paper'],
        'officer.exams.papers.freeze-all' => ['sittings', 'Froze all draft papers'],

        'officer.intakes.store' => ['interns', 'Opened a registration window'],
        'officer.intakes.update' => ['interns', 'Changed a registration window'],
        'officer.applicants.reject' => ['interns', 'Rejected an intern application'],
        'officer.applicants.return' => ['interns', 'Returned an intern application for correction'],

        'results.exams.recalculate' => ['results', 'Recalculated ranks'],
        'results.exams.notify' => ['results', 'Emailed candidates that results are ready'],
        'results.exams.export' => ['results', 'Exported results to CSV'],
        'results.exams.print' => ['results', 'Printed the ranked list'],
    ];

    /** Routes the dedicated loggers already cover, so the request recorder leaves them alone. */
    private const COVERED_ELSEWHERE = [
        'admin.staff.', 'account.password.', 'invigilator.', 'officer.exams.candidates.',
        'officer.exams.lists.', 'officer.applicants.approve', 'results.exams.release', 'results.exams.withdraw',
        'login', 'logout', 'audit.',
    ];

    /** Older log entries: action => [category, what happened]. */
    private const LEGACY = [
        'list_imported' => ['candidates', 'Imported an eligible list'],
        'list_approved' => ['candidates', 'Approved an eligible list'],
        'list_discarded' => ['candidates', 'Discarded an eligible list'],
        'candidate_added' => ['candidates', 'Added a candidate'],
        'candidate_removed' => ['candidates', 'Removed a candidate'],
        'assigned' => ['candidates', 'Assigned a seat'],
        'unassigned' => ['candidates', 'Took away a seat assignment'],
        'auto_assigned' => ['candidates', 'Assigned seats automatically'],
        'extra_time_set' => ['candidates', 'Set extra time'],
        'checked_in' => ['candidates', 'Checked a candidate in'],
        'check_in_undone' => ['candidates', 'Undid a check-in'],
        'admitted' => ['candidates', 'Admitted a candidate'],
        'admitted_all' => ['candidates', 'Admitted all checked-in candidates'],
        'admission_revoked' => ['candidates', 'Withdrew a candidate\'s admission'],
        'pin_reissued' => ['candidates', 'Issued a new PIN'],
        'account_unlocked' => ['candidates', 'Unlocked a candidate\'s account'],
        'marked_absent' => ['candidates', 'Marked a candidate absent'],
        'applicant_approved' => ['interns', 'Approved an intern application'],
        'results_released' => ['results', 'Released results'],
        'results_withdrawn' => ['results', 'Withdrew released results'],
    ];

    private const STAFF = [
        'created' => 'Created a staff account',
        'updated' => 'Changed a staff account',
        'deactivated' => 'Deactivated a staff account',
        'reactivated' => 'Reactivated a staff account',
        'password_reset' => 'Reset a staff password',
        'password_changed' => 'Changed their own password',
    ];

    public const ATTEMPT_EVENTS = [
        'started' => 'Started the exam',
        'resumed' => 'Signed back in and resumed',
        'tab_hidden' => 'Left the exam tab',
        'tab_visible' => 'Came back to the exam tab',
        'fullscreen_exit' => 'Left full screen',
        'question_timed_out' => 'A question timed out',
        'submitted' => 'Submitted the exam',
    ];

    public static function route(string $name): ?array
    {
        return self::ROUTES[$name] ?? null;
    }

    /** Is this route one the request recorder should log? */
    public static function recordsRoute(?string $name): bool
    {
        if (! $name || ! isset(self::ROUTES[$name])) {
            return false;
        }

        foreach (self::COVERED_ELSEWHERE as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return false;
            }
        }

        return true;
    }

    /** @return array{0: string, 1: string} category and wording for an older registration-log action */
    public static function legacy(string $action): array
    {
        return self::LEGACY[$action] ?? ['candidates', ucfirst(str_replace('_', ' ', $action))];
    }

    public static function staff(string $action): string
    {
        return self::STAFF[$action] ?? ucfirst(str_replace('_', ' ', $action));
    }

    public static function isSensitive(string $action): bool
    {
        return in_array($action, self::SENSITIVE, true)
            || in_array(preg_replace('/^(reg|staff)\./', '', $action), self::SENSITIVE, true);
    }
}
