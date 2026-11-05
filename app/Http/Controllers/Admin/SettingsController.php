<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private Settings $settings) {}

    public function edit(): View
    {
        $saved = $this->settings->rows();

        $groups = collect(config('settings.groups'))->map(function ($group, $groupKey) use ($saved) {
            $items = collect($this->settings->definitions())
                ->filter(fn ($d) => $d['group'] === $groupKey)
                ->map(function ($d, $key) use ($saved) {
                    $value = $this->settings->get($key);

                    return $d + [
                        'key' => $key,
                        'field' => self::field($key),
                        'value' => $value,
                        'changed' => $value !== $d['default'],
                        'row' => $saved->get($key),
                    ];
                })->values();

            return $group + ['items' => $items];
        });

        return view('admin.settings.edit', ['groups' => $groups, 'anyChanged' => $saved->isNotEmpty()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];
        $names = [];

        foreach ($this->settings->definitions() as $key => $d) {
            $field = self::field($key);
            $rules[$field] = ['required', 'integer', 'between:'.$d['min'].','.$d['max']];
            $names[$field] = strtolower($d['label']);
        }

        $data = $request->validate($rules, [
            'required' => 'Enter a number for :attribute.',
            'integer' => 'Use a whole number for :attribute.',
            'between' => 'For :attribute, use a number from :min to :max.',
        ], $names);

        $values = collect($this->settings->definitions())->mapWithKeys(fn ($d, $key) => [$key => (int) $data[self::field($key)]])->all();

        if ($values['exam.critical_seconds'] >= $values['exam.warn_seconds']) {
            throw ValidationException::withMessages([self::field('exam.critical_seconds') => 'The red warning must come later than the amber one, so use fewer seconds for red than for amber.']);
        }

        $changes = $this->settings->save($values, $request->user());

        return $this->saved($changes, 'Changed system settings');
    }

    public function reset(Request $request): RedirectResponse
    {
        $changes = $this->settings->reset($request->user());

        return $this->saved($changes, 'Put system settings back to the standard values');
    }

    /** @param  array<string, array{from: int, to: int}>  $changes */
    private function saved(array $changes, string $what): RedirectResponse
    {
        if (! $changes) {
            return redirect()->route('admin.settings.edit')->with('status', 'Nothing changed.');
        }

        $defs = $this->settings->definitions();
        $diff = collect($changes)->mapWithKeys(fn ($c, $key) => [$defs[$key]['label'].' ('.$defs[$key]['unit'].')' => $c])->all();

        Audit::record('admin.settings.update', 'settings', $what.' · '.collect($diff)->map(fn ($c, $label) => "{$label}: {$c['from']} → {$c['to']}")->take(3)->implode('; ').(count($diff) > 3 ? ' and '.(count($diff) - 3).' more' : ''), [
            'meta' => ['changes' => [['model' => 'Settings', 'id' => null, 'label' => '', 'changed' => $diff]]],
        ]);

        return redirect()->route('admin.settings.edit')->with('status', count($changes).' setting'.(count($changes) === 1 ? '' : 's').' changed. The new rules apply from now on.');
    }

    /** A form field name for a setting; dots in the key would be read as nesting. */
    public static function field(string $key): string
    {
        return 's_'.str_replace('.', '__', $key);
    }
}
