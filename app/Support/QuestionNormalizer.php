<?php

namespace App\Support;

class QuestionNormalizer
{
    public const MIN_OPTIONS = 2;
    public const MAX_OPTIONS = 6;

    /**
     * Check a question's options and put them in their final form.
     *
     * @param  string  $type     single_choice | multi_select | true_false
     * @param  array   $bodies   option texts in order; blank ones are ignored
     * @param  int[]   $correct  indexes into $bodies. For true_false: 0 = True, 1 = False
     * @return array{options: array, errors: string[]}
     */
    public static function normalize(string $type, array $bodies, array $correct): array
    {
        $errors = [];
        $correct = array_values(array_unique($correct));

        if ($type === 'true_false') {
            if (count($correct) !== 1 || ! in_array($correct[0], [0, 1], true)) {
                $errors[] = 'Choose whether the statement is true or false.';
            }

            return [
                'options' => $errors ? [] : [
                    ['body' => 'True', 'is_correct' => $correct[0] === 0, 'pin_last' => false, 'position' => 1],
                    ['body' => 'False', 'is_correct' => $correct[0] === 1, 'pin_last' => false, 'position' => 2],
                ],
                'errors' => $errors,
            ];
        }

        // Drop blank options, remembering where each one moved to
        $kept = [];
        $moved = [];
        foreach ($bodies as $i => $body) {
            $body = trim((string) $body);
            if ($body === '') {
                continue;
            }
            $moved[$i] = count($kept);
            $kept[] = $body;
        }

        if (count($kept) < self::MIN_OPTIONS || count($kept) > self::MAX_OPTIONS) {
            $errors[] = 'A question needs between '.self::MIN_OPTIONS.' and '.self::MAX_OPTIONS.' options.';
        }

        $lower = array_map(fn ($b) => mb_strtolower($b), $kept);
        if (count($lower) !== count(array_unique($lower))) {
            $errors[] = 'Two options have the same text.';
        }

        foreach ($kept as $body) {
            if (self::refersToOtherOptions($body)) {
                $errors[] = "The option \"{$body}\" refers to other options by letter. Options are shuffled for each candidate, so spell the answer out instead.";
            }
        }

        $correctKept = [];
        foreach ($correct as $i) {
            if (! isset($moved[$i])) {
                $errors[] = 'The correct answer cannot be an empty option.';
                continue;
            }
            $correctKept[] = $moved[$i];
        }

        if ($type === 'single_choice' && count($correctKept) !== 1) {
            $errors[] = 'Choose exactly one correct answer.';
        }
        if ($type === 'multi_select' && count($correctKept) < 1) {
            $errors[] = 'Choose at least one correct answer.';
        }

        if ($errors) {
            return ['options' => [], 'errors' => array_values(array_unique($errors))];
        }

        $options = [];
        foreach ($kept as $i => $body) {
            $options[] = [
                'body' => $body,
                'is_correct' => in_array($i, $correctKept, true),
                // "All of the above" and "None of the above" stay last when options are shuffled
                'pin_last' => (bool) preg_match('/^(all|none)\s+of\s+the\s+(above|these)\.?$/iu', $body),
            ];
        }

        // Pinned options go to the end of the list
        $options = array_merge(
            array_values(array_filter($options, fn ($o) => ! $o['pin_last'])),
            array_values(array_filter($options, fn ($o) => $o['pin_last'])),
        );
        foreach ($options as $i => &$o) {
            $o['position'] = $i + 1;
        }

        return ['options' => $options, 'errors' => []];
    }

    private static function refersToOtherOptions(string $body): bool
    {
        return (bool) (
            preg_match('/\b(both|neither)\s+[A-F]\s*(and|&|,)\s*[A-F]\b/iu', $body)
            || preg_match('/^[A-F]\s*(and|&|,)\s*[A-F]$/iu', $body)
            || preg_match('/\b(option|answer|choice)s?\s+[A-F]\b/iu', $body)
        );
    }
}
