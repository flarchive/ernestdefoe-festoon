<?php

namespace Ernestdefoe\Festoon\Tests\unit;

use Flarum\Settings\SettingsRepositoryInterface;

/** Settings held in an array, for tests that need no database. */
class ArraySettings implements SettingsRepositoryInterface
{
    public function __construct(private array $values = [])
    {
    }

    public function all(): array
    {
        return $this->values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    public function delete(string $keyLike): void
    {
        unset($this->values[$keyLike]);
    }
}
