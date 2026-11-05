<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;
use Throwable;

/**
 * Reads and saves the system settings listed in config/settings.php.
 * Registered as a singleton, so a request reads the table once and a saved change shows at once.
 */
class Settings
{
    /** @var array<string, string>|null */
    private ?array $stored = null;

    /** The value in force for a setting: the saved one, or the standard if none (or if the saved one is out of range). */
    public static function int(string $key): int
    {
        return app(self::class)->get($key);
    }

    public function get(string $key): int
    {
        $def = $this->definition($key);
        $value = $this->stored()[$key] ?? null;

        return is_numeric($value) && $value >= $def['min'] && $value <= $def['max'] ? (int) $value : $def['default'];
    }

    /** @return array<string, array<string, mixed>> */
    public function definitions(): array
    {
        return config('settings.items');
    }

    /** @return array<string, array<string, mixed>> */
    public function definition(string $key): array
    {
        // The names contain dots, so look them up in the array rather than with config()'s dot notation
        return config('settings.items')[$key] ?? throw new \InvalidArgumentException("Unknown setting [{$key}].");
    }

    /** @return array<string, int> every setting with its value in force */
    public function all(): array
    {
        return collect($this->definitions())->map(fn ($d, $key) => $this->get($key))->all();
    }

    /**
     * Save new values. Only differences are written, and a value equal to the standard removes its row.
     *
     * @param  array<string, int>  $values
     * @return array<string, array{from: int, to: int}> what changed
     */
    public function save(array $values, ?User $by): array
    {
        $changes = [];

        foreach ($values as $key => $new) {
            $def = $this->definition($key);
            $old = $this->get($key);
            $new = (int) $new;

            if ($new === $old) {
                continue;
            }

            if ($new === $def['default']) {
                Setting::where('key', $key)->delete();
            } else {
                Setting::updateOrCreate(['key' => $key], ['value' => (string) $new, 'updated_by' => $by?->id]);
            }

            $changes[$key] = ['from' => $old, 'to' => $new];
        }

        $this->stored = null;

        return $changes;
    }

    /** Put everything back to the standard values. */
    public function reset(?User $by): array
    {
        return $this->save(collect($this->definitions())->map(fn ($d) => $d['default'])->all(), $by);
    }

    /** @return \Illuminate\Support\Collection<string, Setting> the saved rows, for "last changed by" */
    public function rows(): \Illuminate\Support\Collection
    {
        try {
            return Setting::with('updatedBy')->get()->keyBy('key');
        } catch (Throwable) {
            return collect();
        }
    }

    /** @return array<string, string> */
    private function stored(): array
    {
        if ($this->stored === null) {
            try {
                $this->stored = Setting::pluck('value', 'key')->all();
            } catch (Throwable) {
                $this->stored = []; // before the table exists (for example while migrating) the standards apply
            }
        }

        return $this->stored;
    }
}
