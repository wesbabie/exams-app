# Exams app: candidate pages, staff login, exam officer screens and the question bank

## 1. Copy the files
Copy everything into `exams-app`, keeping the same paths, and overwrite the earlier files.
(`routes/staff.php` now also loads `routes/bank.php`, so there is no new line to add in `web.php`.)

Old layouts you can delete if still present:
- `resources/views/layouts/candidate.blade.php`
- `resources/views/layouts/candidate-dashboard.blade.php`

## 2. Database
There is one new migration (review note and submitted time on questions):

```
php artisan migrate
php artisan db:seed --class=DemoStaffSeeder
php artisan db:seed --class=DemoCandidateSeeder
php artisan db:seed --class=DemoBankSeeder
```

`DemoBankSeeder` adds three topics and eight approved sample questions. Development only.

## 3. Try it
Keep `npm run dev` running. Sign in at `/login`. All staff accounts use the password `password`.

| Email | Role | What they can do in the bank |
|---|---|---|
| author@exams.test | Question author | Write, import and send their own questions for review. They only see their own questions. |
| reviewer@exams.test | Reviewer | Approve or return questions. Lands on the review queue. |
| officer@exams.test | Exam officer | Everything, plus add topics. |
| admin@exams.test | Super admin | Everything. |

Suggested walk-through:
1. As **officer**: open **Topics** and add one (for example "Pharmacology", scope: only Demo Cadre I).
2. As **author**: **New question**, try each type. Then **Import CSV**: download the template, change the topic names to ones that exist, and upload it. Try a file with a mistake to see the row errors.
3. As **author**: **Send all my drafts for review**.
4. As **reviewer**: open the queue, tick several questions and **Approve selected**, or open one and **Return with note**.
5. As **author**: open the returned question, fix it, and send it again.
6. As **officer**: open an approved question and **Create new version**. Approve the new version as reviewer and the old one becomes retired.

## Rules built in
- Only the author (or an exam officer / super admin) can edit a draft. Approved questions are never edited in place; a new version is created instead.
- Nobody can review their own question.
- Options cannot refer to each other by letter ("both A and B"). "All of the above" and "None of the above" are pinned last.
- Single choice has exactly one correct answer. Multi-select has at least one. True/false is fixed at True and False.
- The CSV import is all-or-nothing: if any row has a problem, nothing is saved.

## Not built yet
- Images in questions and case-scenario groups (the tables exist; the screens do not).
- Adding approved questions to an exam's sections, and drawing papers.
- Excel (.xlsx) import. Save as CSV for now.
- A history of who changed what. Add spatie/laravel-activitylog when you want an audit trail.

---

# Step 6: questions in exams

No new migration and no new route line. `routes/officer.php` gained the question routes.

## Try it
1. As **officer**: create an exam in the category "Demo Cadre I", with three sections of 2 questions each (the staff layout button fills in three sections; change the counts to 2). Fixed set is the default.
2. On the exam page, each section now has a **Question set** column (for example `0 / 2  Choose`). Open one:
   - Left: the questions chosen so far, with up, down and remove buttons.
   - Right: approved questions from the bank. Shared topics (General Knowledge, Civil Service Rules) and the category's own topics (Clinical Practice) are listed. Tick and **Add ticked questions**.
   - A question can only be used once in an exam, so chosen ones disappear from the list.
3. The **Readiness** card on the exam page lists what is missing. **Send for review** is blocked until every section has exactly the right number of approved questions.
4. For a pool-draw exam, edit the exam, switch to **Pool draw**, and save. Each section then shows **Set counts**: choose how many questions to draw from each topic. The page shows the pool size against the draw, and flags a pool smaller than 1.5 times the draw.

## Rules built in
- Only a draft exam's questions can be changed. Approved exams are locked.
- Only approved questions can be chosen. If a chosen question is retired later (for example when a new version replaces it), the section is flagged and the exam cannot be sent for review or approved until it is replaced.
- Switching an exam between fixed set and pool draw clears the chosen questions and topic counts, because they belong to one mode.
- The topic counts for a section cannot add up to more than its question count, and a topic cannot be asked for more questions than it has approved.

---

# Step 7: eligible list, sittings and seats

## Before you start: set the timezone
Sitting times are stored and shown in the app's timezone, which Laravel sets to UTC by default. Add this to `.env` **before creating sittings**, so times match the clock in the lab:

```
APP_TIMEZONE=Africa/Lagos
```

Then run `php artisan config:clear`. The Sittings page shows the server time so you can check it.

## Database
Two new migrations (a small activity log for the candidate list, and a link from registrations to the list they came from):

```
php artisan migrate
```

## Try it
1. As **officer**: open an exam in a staff category, then **Candidates**. Click **Download template**. It is pre-filled with the exam's category in the cadre column. Edit the file (use your own file numbers) and **Import list**.
   - Try a file with a duplicate file number, or a cadre that does not match the exam, to see the row errors. Nothing is saved if any row is wrong.
2. The list shows as **Pending**. You imported it, so you cannot approve it. Sign out and sign in as **admin@exams.test** (or another exam officer), open the exam's Candidates page and **Approve list**. The list is now locked and the candidates are **Approved**.
3. **Sittings**: add one or two (name, venue, start, end, seats). The sitting window must be at least as long as the exam.
4. Back on **Candidates**, click **Assign them automatically**, or assign and move people one by one with the dropdown on each row. Seats are numbered from 1.
5. **Add a candidate** (late addition) and **Remove** a candidate both ask for a reason. See them in **Recent activity**.

## Rules built in
- Whoever imports a list cannot approve it.
- An approved list is locked. Changes after that are individual additions or removals, each with a reason and a log entry.
- Importing is all-or-nothing, and checks: missing file number or names, duplicates inside the file, candidates already on the exam, a cadre and level that do not match the exam's category, and invalid emails.
- File numbers are stored in capitals. Re-importing an existing file number updates that person's details (for example after a promotion).
- A sitting cannot be deleted while candidates are assigned to it, and its seats cannot be lowered below the number assigned.
- Closing a sitting stops candidates signing in to it.

## Not built yet
- Intern self-registration (the portal, verification and document review). Intern exams have no candidates until that exists.
- Invigilator check-in, which is where candidates are admitted and PINs are issued.
- Generating the paper for each sitting.

---

# Step 8: check-in, PINs and admission

## Database
One small migration (a file number on staff user accounts, used to stop a candidate from invigilating their own exam):

```
php artisan migrate
```

## Try it
You need an exam with an approved candidate list assigned to a sitting (Step 7). Check-in opens **2 hours before the sitting starts** and closes when it ends, so give your test sitting a window that includes the current time.

1. Sign in as **invigilator@exams.test** / `password`. The top menu now has **Check-in**.
2. Open today's sitting. Search by file number or name, or scan the list in seat order.
3. For a candidate: tick **ID checked** and **Check in**. A printable **slip** opens with a random 6-digit PIN. It is shown **once**. Print it or copy it.
4. The candidate is now **Checked in** but cannot sign in yet. Use **Admit** for one candidate, or **Admit all checked in** when you are ready to start.
5. The candidate opens `/exam/login`, signs in with their file number and PIN, and lands on their dashboard.
6. Other actions on each row:
   - **New PIN**: issues a fresh PIN and the old one stops working.
   - **Withdraw admission**: the candidate is signed out on their next click.
   - **Undo** (before admission): returns them to "not arrived" and voids the PIN.
   - **Absent**: for someone who has not arrived.
   - **Unlock**: appears when a candidate is locked after 5 wrong PINs.
7. Exam officers and the super admin can use Check-in too. Everything is recorded in the exam's **Recent activity** (on its Candidates page).

## Stopping a candidate from invigilating their own exam
Add the person's file number to their staff account. If that file number is a candidate in an exam, they cannot open that exam's sittings in Check-in, and those sittings are hidden from their list.

```
php artisan tinker
>>> App\Models\User::where('email', 'invigilator@exams.test')->update(['file_number' => 'TEST/0001']);
```

There is no screen for editing staff accounts yet, so this is set by hand for now. The same check still needs adding to the exam officer screens when staff account management is built.

## Rules built in
- The PIN is random, stored only as a hash, valid for one sitting (until an hour after it ends), and shown once.
- A candidate cannot sign in until an invigilator admits them, and the sitting window must be open.
- Withdrawing admission or locking an account takes effect on the candidate's next click.
- Check-in needs the "ID checked" box ticked.

## Not built yet
- Assigning specific invigilators to a sitting (every invigilator currently sees all of today's sittings).
- Paper generation and the exam screen: after signing in, **Start exam** is still a placeholder.

---

# Step 9: paper generation and the exam screen

## Database
No new migrations. (The tables for papers, attempts, answers and results already exist.)

```
php artisan db:seed --class=DemoExamSeeder
```

This needs `DemoStaffSeeder` and `DemoBankSeeder` first. It creates a complete small exam ("Demo Exam (ready to run)", 8 questions in 3 sections) with an open sitting, a **frozen paper**, and two candidates, **TEST/0002** and **TEST/0003**, assigned to seats. It is safe to run again: it resets the sitting and the two candidates (and deletes their attempts) so you can repeat the whole run.

## Run a whole exam
1. **Invigilator** (`invigilator@exams.test` / `password`): Check-in, open the sitting, **Check in** TEST/0002 (tick "ID checked"), note the PIN on the slip, then **Admit**.
2. **Candidate**: `/exam/login`, file number `TEST/0002` and the PIN. On the dashboard tick the rules box and **Start exam**.
3. Each section starts with an introduction screen. The timer for its first question starts when you press **Begin section**.
4. Answer questions: your choice is saved as you tick it ("Saved ✓"). **Next question** moves on, and the timer running out moves on by itself. You cannot go back. Try refreshing the page: the same question comes back with the remaining time, because the server holds the deadline.
5. The last button says **Finish exam**. The candidate sees a confirmation page, no scores.
6. As the invigilator, the candidate shows as **Writing** during the exam and **Finished** after. Check the database tables `results` and `section_results` for the scores.

Things to try: sign in as the same candidate in a second browser (the first is signed out); switch to another browser tab during a question (an `attempt_events` row is written); lock the account or **Withdraw admission** mid-exam (the candidate is sent back to the login page on their next click); sign in again and **Resume exam**.

## Generating papers for your own exams (officer)
1. Build an exam, add its questions (or topic counts), send it for review and approve it (as before).
2. **Sittings**: each sitting now has a **Paper** column. **Generate paper**, **Preview** it (the preview shows the correct answers), then **Freeze**. The buttons at the top do it for every sitting at once.
3. Fixed-set exams copy the chosen questions. Pool-draw exams draw the topic counts at random, preferring questions that earlier sittings of the same exam did not use.
4. Freezing the first paper moves the exam from Approved to Scheduled. A frozen paper never changes, and candidates cannot start until their sitting's paper is frozen.

## How scoring works
- A question is scored when it is closed (next, or time running out). Single choice and true/false: the one correct option. **Multi-select: all-or-nothing** (the selection must match the correct options exactly). This was left open earlier; it is one small function, `AttemptService::score()`, if you want partial credit instead.
- Wrong answers cost the exam's negative-marking fraction of the mark; unanswered scores zero. The total is never below zero.
- The result (total, percentage, pass or fail, per-section marks) is written when the exam is submitted. Ranks are not calculated yet.

## Unfinished exams
If a candidate's computer dies and they never come back, their attempt stays open. This command submits any attempt left unfinished after its sitting ended (plus the longest the exam could still run, plus ten minutes):

```
php artisan exams:finalize-attempts
```

To run it automatically, add this to `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('exams:finalize-attempts')->everyFiveMinutes();
```

(and make sure the Laravel scheduler is running on the server).

## Rules built in
- The server holds every deadline. The browser only shows a countdown, so refreshing, a second tab or a different computer cannot extend the time.
- A selection is accepted up to 2 seconds after the deadline (network lag). After that the question is closed with whatever was saved.
- Questions are shuffled within each section (if the exam says so), and options are shuffled per candidate, with "All of the above" and "None of the above" kept last and true/false fixed. Each candidate's own order is stored.
- Signing in on another computer switches off the first one.
- Leaving the tab is recorded and flagged for review. It is never an automatic penalty.
- After the sitting window ends, a candidate who has already started can still finish.

## Not built yet
- Results screens for exam officers and the committee (ranking, tie-breaks, shortlisting, release, exports).
- Intern self-registration.
- Showing the integrity events (tab switches) to invigilators and officers.

---

# Step 10: results

No new migrations. The top menu gains **Results** for exam officers, the super admin and the committee (the committee can read, export and print but not release).

## Try it
Run an exam first (Step 9: `DemoExamSeeder`, check in TEST/0002 and TEST/0003, let them take the exam; you can answer some right and some wrong). Then:

1. **Results** lists the exams. Open **Demo Exam (ready to run)**.
2. The page shows how many submitted and passed, the average and highest score, the average by sitting, and the ranked table with each section's marks, the total, the percentage, pass or fail, and tab-switch flags.
3. **Review** (officers only) opens one candidate's attempt: each question in the order they saw it, what they chose, the correct answer and the marks, plus their activity log.
4. **Question analysis** shows each question's percentage correct, hardest first, and flags questions that nearly everyone got wrong (check the answer key) or right.
5. **Release results** saves the ranks and the shortlist and marks the results released. It is blocked while anyone is still writing. After release you can **Print all result slips** (one per candidate, one per page) and **Withdraw release** if you need to.
6. **Export CSV (Excel)** and **Print ranked list** (use the browser's "Save as PDF") are available to the committee too.
7. Sign in as `committee@exams.test`: **Results** shows the same ranked list, without the release, review and analysis controls.

## Rules built in
- Ranking is by total marks. Candidates tied on every rule share a rank (1, 2, 2, 4). The exam's tie-break applies: the **field section** score (the section with "field" in its title, otherwise the last section) or the **earliest submission**.
- The shortlist is the top N among those who passed. If candidates tie at the cut-off, all of them are shortlisted and the page says so.
- Result slips follow the exam's "What candidates see" setting: pass or fail only, or the section scores and total. A "nothing" setting blocks slips. Ranks are never shown to candidates.
- Tab-switch flags are for review only and never change a score.

## Not built yet
- Candidates cannot look up their own result yet. After the sitting ends they cannot sign in, and a safe way to check a result (what they would prove who they are with) is still to be decided. For now the exam office releases results and prints slips.
- Notifications by SMS or email when results are released.
- Intern self-registration.

---

# Step 11: staff accounts and extra time

## Database
Two new migrations (must-change-password and last sign-in on users, and a log of account changes):

```
php artisan migrate
```

## Try it (as the super admin, `admin@exams.test`)
1. **Staff** (new menu item) lists every account with role, file number, status and last sign-in, and has search and filters. The list ends with recent activity.
2. **New account**: name, email, role, and an optional file number. A temporary password is generated and shown **once** on the next screen (with a Copy button). Give it to the person privately.
3. Sign in as that person: before anything else they are sent to **Choose your own password** (at least 10 characters, with capital and small letters and a number). After that the account works normally. Anyone can later change their password by clicking their name at the top.
4. **Manage** an account to change its name, email, role or file number, to **Reset password** (a new temporary password, and the person is signed out), or to **Deactivate** (signed out straight away; reactivate any time). Accounts are never deleted, so the record of who did what stays intact.
5. Safeguards: you cannot deactivate yourself or change your own role, and the last active super admin cannot be demoted or deactivated.

## File numbers and conflicts of interest
Putting a person's file number on their account switches on the rule from Step 8: if that file number is a candidate in an exam, they cannot invigilate its sittings. It also needs to be set for any staff member who is likely to sit a promotion exam.

## Extra time for candidates
On an exam's **Candidates** page, each row has an **Extra time** control: none, ×1.25, ×1.5 or ×2, with a reason (required) that is logged. Every question's timer for that candidate is lengthened by that factor, and their dashboard shows the longer timers. It cannot be changed once the candidate has started.

## Rules built in
- A deactivated person is signed out on their next click, and cannot sign in again until reactivated.
- Temporary passwords are random, 12 characters, and never stored in plain text. They only exist on the one screen that shows them.
- The demo accounts from `DemoStaffSeeder` (password `password`) are for development only. Before going live, run the app without that seeder, create the real super admin once (in `php artisan tinker`, or by seeding one account), and create everyone else from this screen.

## Not built yet
- Password reset by email ("forgot password"). For now a super admin resets passwords. This needs mail set up on the server.
- Two-factor sign-in for admins and exam officers.
