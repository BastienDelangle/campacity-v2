<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/**
 * Paramètres métier administrables. Valeurs par défaut dans config/campacity.php,
 * surcharges en base (table `settings`), mises en cache.
 */
class Settings
{
    private const CACHE_KEY = 'campacity.settings';

    /** @var array<string, mixed>|null */
    private ?array $overrides = null;

    public function get(string $key): mixed
    {
        $this->ensureKnown($key);

        return array_key_exists($key, $this->overrides())
            ? $this->overrides()[$key]
            : config("campacity.settings.{$key}");
    }

    public function int(string $key): int
    {
        return (int) $this->get($key);
    }

    public function bool(string $key): bool
    {
        return (bool) $this->get($key);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->ensureKnown($key);
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
        $this->overrides = null;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return collect(array_keys(config('campacity.settings')))
            ->mapWithKeys(fn (string $key) => [$key => $this->get($key)])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function overrides(): array
    {
        return $this->overrides ??= Cache::rememberForever(
            self::CACHE_KEY,
            fn () => Setting::query()->pluck('value', 'key')->all(),
        );
    }

    private function ensureKnown(string $key): void
    {
        if (! array_key_exists($key, config('campacity.settings'))) {
            throw new InvalidArgumentException("Paramètre inconnu : {$key}");
        }
    }
}
