<?php declare(strict_types=1);

/*
 * System settings: the rules the whole system follows that an administrator may need to change.
 * Each has a standard value and a range it must stay inside, so a typing slip cannot switch off a
 * safety limit. Anything not listed here (the 6-digit PIN length, for example) is deliberately fixed.
 */
return [
    'groups' => [
        'exam_day' => ['title' => 'Exam day', 'text' => 'Check-in, PINs and the exam clock.'],
        'results' => ['title' => 'Candidates checking their results', 'text' => 'How long they can read, and how hard it is to guess a PIN.'],
        'interns' => ['title' => 'Intern applications', 'text' => 'Email verification and passwords for applicants.'],
        'staff' => ['title' => 'Staff accounts', 'text' => 'Rules for the people who run the system.'],
    ],

    'items' => [
        // ---- Exam day
        'checkin.opens_hours_before' => [
            'group' => 'exam_day', 'label' => 'Check-in opens before a sitting starts', 'unit' => 'hours', 'default' => 2, 'min' => 0, 'max' => 24,
            'help' => 'Invigilators cannot check candidates in earlier than this.',
        ],
        'exam.pin_grace_minutes' => [
            'group' => 'exam_day', 'label' => 'A PIN still works after the sitting ends for', 'unit' => 'minutes', 'default' => 60, 'min' => 0, 'max' => 720,
            'help' => 'Only matters for finishing an exam that is already under way.',
        ],
        'exam.max_wrong_pins' => [
            'group' => 'exam_day', 'label' => 'Wrong PINs before an account locks', 'unit' => 'tries', 'default' => 5, 'min' => 3, 'max' => 10,
            'help' => 'An invigilator unlocks it. Lower is safer.',
        ],
        'exam.timer_grace_seconds' => [
            'group' => 'exam_day', 'label' => 'Extra time the server allows for a slow connection', 'unit' => 'seconds', 'default' => 2, 'min' => 0, 'max' => 10,
            'help' => 'An answer arriving this late after the question closed is still accepted.',
        ],
        'exam.warn_seconds' => [
            'group' => 'exam_day', 'label' => 'The clock turns amber when this many seconds are left', 'unit' => 'seconds', 'default' => 10, 'min' => 5, 'max' => 60,
            'help' => 'A quiet visual warning. There is no sound.',
        ],
        'exam.critical_seconds' => [
            'group' => 'exam_day', 'label' => 'The clock turns red when this many seconds are left', 'unit' => 'seconds', 'default' => 5, 'min' => 2, 'max' => 30,
            'help' => 'Must be lower than the amber warning.',
        ],
        'exam.tab_grace_seconds' => [
            'group' => 'exam_day', 'label' => 'Leaving the exam tab counts only after', 'unit' => 'seconds', 'default' => 1, 'min' => 1, 'max' => 10,
            'help' => 'Shorter glances are ignored. Leaving the tab is flagged for review, never penalised automatically.',
        ],

        // ---- Results
        'results.read_minutes' => [
            'group' => 'results', 'label' => 'A candidate can read their results for', 'unit' => 'minutes', 'default' => 30, 'min' => 5, 'max' => 120,
            'help' => 'After this they must enter their number and PIN again.',
        ],
        'results.pin_tries' => [
            'group' => 'results', 'label' => 'Wrong PINs before results lock for a number', 'unit' => 'tries', 'default' => 5, 'min' => 3, 'max' => 10,
            'help' => 'Lower is safer. The PIN is only 6 digits.',
        ],
        'results.pin_lock_minutes' => [
            'group' => 'results', 'label' => 'How long that lock lasts', 'unit' => 'minutes', 'default' => 15, 'min' => 5, 'max' => 120,
            'help' => 'Longer is safer against guessing.',
        ],
        'results.pin_tries_per_day' => [
            'group' => 'results', 'label' => 'Most wrong PINs for one number in a day', 'unit' => 'tries', 'default' => 20, 'min' => 5, 'max' => 50,
            'help' => 'Past this the exam office has to help.',
        ],
        'results.code_minutes' => [
            'group' => 'results', 'label' => 'An emailed code lasts', 'unit' => 'minutes', 'default' => 15, 'min' => 5, 'max' => 60,
            'help' => 'For staff who lost their PIN.',
        ],

        // ---- Interns
        'intern.otp_minutes' => [
            'group' => 'interns', 'label' => 'An emailed verification code lasts', 'unit' => 'minutes', 'default' => 15, 'min' => 5, 'max' => 60,
            'help' => '',
        ],
        'intern.otp_max_attempts' => [
            'group' => 'interns', 'label' => 'Wrong verification codes before it locks', 'unit' => 'tries', 'default' => 5, 'min' => 3, 'max' => 10,
            'help' => 'The applicant then asks for a new code.',
        ],
        'intern.otp_resend_seconds' => [
            'group' => 'interns', 'label' => 'Wait before another code can be sent', 'unit' => 'seconds', 'default' => 60, 'min' => 30, 'max' => 300,
            'help' => '',
        ],
        'intern.min_password_length' => [
            'group' => 'interns', 'label' => 'Shortest applicant password', 'unit' => 'characters', 'default' => 8, 'min' => 8, 'max' => 32,
            'help' => 'Applies to new accounts. They also need letters and numbers.',
        ],

        // ---- Staff
        'staff.min_password_length' => [
            'group' => 'staff', 'label' => 'Shortest staff password', 'unit' => 'characters', 'default' => 10, 'min' => 6, 'max' => 32,
            'help' => 'Applies when a password is next changed. Staff also need upper and lower case letters and numbers.',
        ],
    ],
];
