<?php

namespace Ernestdefoe\Festoon\Look;

/**
 * One look, its stored changes laid over its defaults and every value checked.
 *
 * Values that reach CSS — colours and the stripe list — are only ever accepted
 * as #rrggbb, and every decoration name is checked against its allow-list, so
 * nothing an administrator saves can break out of the stylesheet.
 */
final class Look
{
    /** What a new look of the administrator's own starts as. */
    public const CUSTOM_DEFAULTS = [
        'date' => ['type' => 'range', 'from' => '01-01', 'to' => '01-01'],
        'primary' => '#536f90', 'secondary' => '#536f90', 'garland' => 'none', 'ornament' => 'none',
        'corner' => 'none', 'hat' => 'none', 'trim' => 'none', 'effect' => 'none',
    ];

    /** @param array<string, mixed> $data */
    private function __construct(public readonly string $id, private array $data)
    {
    }

    /**
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $changes
     */
    public static function make(string $id, array $defaults, array $changes): self
    {
        $group = (string) $defaults['group'];
        $d = $defaults + ['before' => 0, 'after' => 0, 'sky' => null];

        $d['enabled'] = (bool) ($changes['enabled'] ?? Catalogue::defaultEnabled($group));
        $d['priority'] = self::int($changes['priority'] ?? null, Catalogue::defaultPriority($group), 0, 100);
        $d['before'] = self::int($changes['before'] ?? null, (int) $d['before'], 0, 60);
        $d['after'] = self::int($changes['after'] ?? null, (int) $d['after'], 0, 60);

        if (($d['date']['type'] ?? '') === 'range') {
            foreach (['from', 'to'] as $end) {
                $md = (string) ($changes[$end] ?? '');
                if (preg_match('/^(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $md)) {
                    $d['date'][$end] = $md;
                }
            }
        }

        foreach (['primary', 'secondary'] as $c) {
            $d[$c] = self::color($changes[$c] ?? null) ?? $d[$c];
        }

        foreach (['garland' => Catalogue::GARLANDS, 'ornament' => Catalogue::ORNAMENTS, 'corner' => Catalogue::CORNERS, 'hat' => Catalogue::HATS, 'effect' => Catalogue::EFFECTS] as $slot => $allowed) {
            $value = $changes[$slot] ?? $d[$slot];
            $d[$slot] = in_array($value, $allowed, true) ? $value : 'none';
        }

        $d['trim'] = self::cleanTrim($changes['trim'] ?? $d['trim']);
        $d['name'] = mb_substr(trim(strip_tags((string) ($changes['name'] ?? ''))), 0, 60);

        if ($d['effect'] === 'fireworks' && ! is_array($d['sky'])) {
            $d['sky'] = [$d['primary'], $d['secondary'], '#ffffff', '#ffd54f'];
        }

        return new self($id, $d);
    }

    public function group(): string
    {
        return $this->data['group'];
    }

    public function enabled(): bool
    {
        return $this->data['enabled'];
    }

    public function priority(): int
    {
        return $this->data['priority'];
    }

    /** The administrator's name for their own look; built-ins are named by translation. */
    public function name(): string
    {
        return $this->data['name'];
    }

    /** @return array<string, mixed> */
    public function date(): array
    {
        return $this->data['date'];
    }

    /** @return array<string, mixed>|null */
    public function until(): ?array
    {
        return $this->data['until'] ?? null;
    }

    public function before(): int
    {
        return $this->data['before'];
    }

    public function after(): int
    {
        return $this->data['after'];
    }

    public function get(string $slot): string
    {
        return (string) $this->data[$slot];
    }

    /** @return 'none'|'snow'|list<string> */
    public function trim(): string|array
    {
        return $this->data['trim'];
    }

    /** @return list<string> */
    public function sky(): array
    {
        return array_values(array_filter(array_map([self::class, 'color'], (array) $this->data['sky'])));
    }

    /** Everything the admin page shows and edits. @return array<string, mixed> */
    public function toArray(): array
    {
        return ['id' => $this->id] + $this->data;
    }

    public static function color(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^#[0-9a-f]{6}$/i', $value) ? strtolower($value) : null;
    }

    /** @return 'none'|'snow'|list<string> */
    private static function cleanTrim(mixed $value): string|array
    {
        if ($value === 'snow' || $value === 'none') {
            return $value;
        }

        $colors = is_array($value) ? ($value['stripe'] ?? $value) : [];
        $colors = array_values(array_filter(array_map([self::class, 'color'], (array) $colors)));

        return $colors ? array_slice($colors, 0, 4) : 'none';
    }

    private static function int(mixed $value, int $default, int $min, int $max): int
    {
        return is_numeric($value) ? max($min, min($max, (int) $value)) : $default;
    }
}
