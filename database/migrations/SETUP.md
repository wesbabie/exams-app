# Candidate login: setup

Copy the files into `exams-app` keeping the same paths, then make these four edits.

## 1. config/auth.php
Add the candidate guard and its provider:

```php
'guards' => [
    'web' => [ /* ...existing... */ ],
    'candidate' => [
        'driver' => 'session',
        'provider' => 'registrations',
    ],
],

'providers' => [
    'users' => [ /* ...existing... */ ],
    'registrations' => [
        'driver' => 'eloquent',
        'model' => App\Models\ExamRegistration::class,
    ],
],
```

## 2. bootstrap/app.php
Send candidates to their own login page (add `use Illuminate\Http\Request;` at the top):

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->redirectGuestsTo(fn (Request $request) => $request->is('exam/*') ? route('candidate.login') : '/');
    $middleware->redirectUsersTo(fn (Request $request) => $request->is('exam/*') ? route('candidate.start') : '/');
})
```

## 3. app/Providers/AppServiceProvider.php
In `boot()`, register the morph map (add `use Illuminate\Database\Eloquent\Relations\Relation;` and the two model imports):

```php
Relation::enforceMorphMap([
    'staff' => \App\Models\StaffCandidate::class,
    'applicant' => \App\Models\Applicant::class,
]);
```

## 4. routes/web.php
Add one line at the bottom:

```php
require __DIR__.'/candidate.php';
```

## Try it
```
php artisan migrate
php artisan db:seed --class=DemoCandidateSeeder
php artisan serve
```
Open `/exam/login` and sign in with file number `TEST/0001` and PIN `123456`.

Things to try: a wrong PIN five times (the account locks); unlock it with
`App\Models\ExamRegistration::first()->unlock();` in `php artisan tinker`; set `admitted_at` to null
(sign-in is refused until admitted); move the sitting's `ends_at` into the past (sign-in is refused).

## Notes
- File numbers and application numbers are matched in upper case, so the staff import must store them upper case.
- `ExamRegistration::issuePin()` returns a fresh random PIN once and stores only its hash. Check-in will call it.
- Admin and exam-officer login is separate and not part of this package.
