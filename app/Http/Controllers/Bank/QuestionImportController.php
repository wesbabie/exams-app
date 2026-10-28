<?php

namespace App\Http\Controllers\Bank;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Topic;
use App\Support\QuestionNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionImportController extends Controller
{
    private const MAX_ROWS = 1000;
    private const LETTERS = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5];

    public function create(): View
    {
        Gate::authorize('create', Question::class);

        return view('bank.import', ['topics' => Topic::with('category')->orderBy('name')->get()]);
    }

    public function template(): StreamedResponse
    {
        Gate::authorize('create', Question::class);

        $rows = [
            ['topic', 'type', 'question', 'option_a', 'option_b', 'option_c', 'option_d', 'option_e', 'option_f', 'correct', 'explanation'],
            ['General Knowledge', 'single_choice', 'What is the capital city of Nigeria?', 'Lagos', 'Abuja', 'Kano', 'Port Harcourt', '', '', 'B', ''],
            ['Clinical Practice', 'multi_select', 'Which of these are vital signs?', 'Temperature', 'Pulse', 'Eye colour', 'Respiratory rate', '', '', 'A;B;D', ''],
            ['Clinical Practice', 'true_false', 'A normal adult resting heart rate is 60 to 100 beats per minute.', '', '', '', '', '', '', 'true', ''],
        ];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row, ',', '"', '');
            }
            fclose($out);
        }, 'question-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Question::class);

        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        [$valid, $errors] = $this->parse($request->file('file')->getRealPath());

        if ($errors) {
            return redirect()->route('bank.import.create')
                ->with('error', 'Nothing was imported. Fix the rows listed below and upload the file again.')
                ->with('import_errors', array_slice($errors, 0, 50))
                ->with('import_error_total', count($errors));
        }

        $userId = $request->user()->id;

        DB::transaction(function () use ($valid, $userId) {
            foreach ($valid as $row) {
                $question = Question::create([
                    'topic_id' => $row['topic']->id,
                    'exam_category_id' => $row['topic']->exam_category_id,
                    'type' => $row['type'],
                    'stem' => $row['stem'],
                    'explanation' => $row['explanation'],
                    'status' => 'draft',
                    'version' => 1,
                    'created_by' => $userId,
                ]);
                $question->options()->createMany($row['options']);
            }
        });

        return redirect()->route('bank.questions.index', ['status' => 'draft'])
            ->with('status', count($valid).' question(s) imported as drafts. Check them, then use "Send all my drafts for review".');
    }

    /** @return array{0: array, 1: string[]}  [valid rows, errors] */
    private function parse(string $path): array
    {
        $errors = [];
        $valid = [];

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, null, ',', '"', '');

        if (! $header) {
            return [[], ['The file is empty.']];
        }

        $header = array_map(fn ($h) => strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $h))), $header);

        foreach (['topic', 'question', 'correct'] as $required) {
            if (! in_array($required, $header, true)) {
                return [[], ["The file needs a \"{$required}\" column. Download the template to see the columns."]];
            }
        }

        $topicsByName = Topic::all()->groupBy(fn ($t) => mb_strtolower($t->name));
        $line = 1;

        while (($cells = fgetcsv($handle, null, ',', '"', '')) !== false) {
            $line++;

            if (count(array_filter($cells, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue; // blank line
            }
            if (count($valid) + count($errors) >= self::MAX_ROWS) {
                $errors[] = 'The file has more than '.self::MAX_ROWS.' rows. Split it into smaller files.';
                break;
            }

            $cells = array_slice(array_pad($cells, count($header), ''), 0, count($header));
            $row = array_combine($header, array_map('trim', $cells));
            $rowErrors = [];

            // Topic
            $matches = $topicsByName->get(mb_strtolower($row['topic']), collect());
            if ($matches->count() === 0) {
                $rowErrors[] = "the topic \"{$row['topic']}\" does not exist. An exam officer must add it first";
            } elseif ($matches->count() > 1) {
                $rowErrors[] = "the topic \"{$row['topic']}\" exists more than once, so it is ambiguous";
            }

            // Type
            $type = str_replace([' ', '-', '/'], '_', strtolower($row['type'] ?? '')) ?: 'single_choice';
            if (! array_key_exists($type, Question::TYPES)) {
                $rowErrors[] = "unknown type \"{$row['type']}\" (use single_choice, multi_select or true_false)";
            }

            // Question text
            if ($row['question'] === '' || mb_strlen($row['question']) > 5000) {
                $rowErrors[] = 'the question text is missing or too long';
            }

            // Correct answer(s)
            $correct = [];
            if ($type === 'true_false') {
                $tf = strtolower($row['correct']);
                if (in_array($tf, ['true', 'false'], true)) {
                    $correct = [$tf === 'true' ? 0 : 1];
                }
            } else {
                foreach (preg_split('/[;,|\s]+/', trim($row['correct']), -1, PREG_SPLIT_NO_EMPTY) as $token) {
                    $letter = strtoupper($token);
                    if (isset(self::LETTERS[$letter])) {
                        $correct[] = self::LETTERS[$letter];
                    } else {
                        $rowErrors[] = "\"{$token}\" in the correct column is not a letter from A to F";
                    }
                }
            }

            // Options
            $result = ['options' => [], 'errors' => []];
            if (array_key_exists($type, Question::TYPES) && ! $rowErrors) {
                $bodies = [];
                foreach (['option_a', 'option_b', 'option_c', 'option_d', 'option_e', 'option_f'] as $col) {
                    $bodies[] = $row[$col] ?? '';
                }
                $result = QuestionNormalizer::normalize($type, $bodies, $correct);
                $rowErrors = array_merge($rowErrors, $result['errors']);
            }

            if ($rowErrors) {
                foreach ($rowErrors as $message) {
                    $errors[] = "Row {$line}: {$message}.";
                }
                continue;
            }

            $valid[] = [
                'topic' => $matches->first(),
                'type' => $type,
                'stem' => $row['question'],
                'explanation' => ($row['explanation'] ?? '') !== '' ? $row['explanation'] : null,
                'options' => $result['options'],
            ];
        }

        fclose($handle);

        if (! $valid && ! $errors) {
            $errors[] = 'The file has no questions in it.';
        }

        return [$valid, $errors];
    }
}
