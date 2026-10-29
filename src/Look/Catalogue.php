<?php

namespace Ernestdefoe\Festoon\Look;

/**
 * The looks Festoon ships with.
 *
 * Each is a set of defaults the administrator can change; only the changes
 * are stored. `date` is a rule for Calendar\Dates, opened `before` days early
 * and held `after` days late; `until` ends the window on a second date
 * instead (Ramadan runs through Eid). Seasons use a plain month/day range.
 *
 * Priority decides overlaps: a holiday (20) beats a season (10), and a look
 * of the administrator's own (30) beats both. Between equals, the shorter
 * window wins, so a one-day holiday is never hidden by a month-long one.
 *
 * Hats are left off the religious holidays on purpose.
 */
final class Catalogue
{
    public const GROUPS = ['holidays', 'seasons', 'world'];

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        $stripe = fn (string ...$c) => ['stripe' => $c];

        return [
            'newyear' => [
                'group' => 'holidays', 'date' => ['type' => 'date', 'md' => '01-01'], 'before' => 1, 'after' => 1,
                'primary' => '#1f3a5f', 'secondary' => '#c9a227', 'garland' => 'none', 'ornament' => 'sparkles',
                'corner' => 'sparkles', 'hat' => 'party', 'trim' => $stripe('#c9a227', '#1f3a5f'), 'effect' => 'fireworks',
                'sky' => ['#ffd54f', '#ffffff', '#f06292', '#4dd0e1', '#b388ff'],
            ],
            'valentines' => [
                'group' => 'holidays', 'date' => ['type' => 'date', 'md' => '02-14'], 'before' => 7, 'after' => 0,
                'primary' => '#c2185b', 'secondary' => '#f06292', 'garland' => 'hearts', 'ornament' => 'none',
                'corner' => 'hearts', 'hat' => 'none', 'trim' => $stripe('#f48fb1', '#c2185b'), 'effect' => 'hearts',
            ],
            'stpatricks' => [
                'group' => 'holidays', 'date' => ['type' => 'date', 'md' => '03-17'], 'before' => 7, 'after' => 0,
                'primary' => '#2e7d32', 'secondary' => '#c9a227', 'garland' => 'shamrocks', 'ornament' => 'none',
                'corner' => 'shamrock', 'hat' => 'leprechaun', 'trim' => $stripe('#2e7d32', '#c9a227'), 'effect' => 'none',
            ],
            'easter' => [
                'group' => 'holidays', 'date' => ['type' => 'easter'], 'before' => 7, 'after' => 0,
                'primary' => '#6a4fb3', 'secondary' => '#9ccc65', 'garland' => 'eggs', 'ornament' => 'none',
                'corner' => 'egg', 'hat' => 'bunny', 'trim' => $stripe('#f8bbd0', '#b3e5fc', '#fff59d', '#c5e1a5'), 'effect' => 'petals',
            ],
            'july4' => [
                'group' => 'holidays', 'date' => ['type' => 'date', 'md' => '07-04'], 'before' => 6, 'after' => 0,
                'primary' => '#b22234', 'secondary' => '#3c3b6e', 'garland' => 'bunting-usa', 'ornament' => 'none',
                'corner' => 'stars', 'hat' => 'tophat', 'trim' => $stripe('#b22234', '#ffffff', '#3c3b6e'), 'effect' => 'fireworks',
                'sky' => ['#e53935', '#ffffff', '#42a5f5', '#ffd54f'],
            ],
            'halloween' => [
                'group' => 'holidays', 'date' => ['type' => 'date', 'md' => '10-31'], 'before' => 16, 'after' => 0,
                'primary' => '#ba4a00', 'secondary' => '#5b2a86', 'garland' => 'bunting-halloween', 'ornament' => 'bat',
                'corner' => 'web', 'hat' => 'witch', 'trim' => $stripe('#f07f13', '#5b2a86'), 'effect' => 'embers',
            ],
            'thanksgiving' => [
                'group' => 'holidays', 'date' => ['type' => 'weekday', 'month' => 11, 'weekday' => 4, 'nth' => 4], 'before' => 7, 'after' => 3,
                'primary' => '#a0522d', 'secondary' => '#d98a1e', 'garland' => 'leaves', 'ornament' => 'none',
                'corner' => 'leaves', 'hat' => 'none', 'trim' => $stripe('#c2551b', '#d98a1e', '#9e3d12'), 'effect' => 'leaves',
            ],
            'christmas' => [
                'group' => 'holidays', 'date' => ['type' => 'date', 'md' => '12-25'], 'before' => 24, 'after' => 1,
                'primary' => '#b71c1c', 'secondary' => '#2e7d32', 'garland' => 'lights', 'ornament' => 'none',
                'corner' => 'holly', 'hat' => 'santa', 'trim' => 'snow', 'effect' => 'snow',
            ],

            'spring' => [
                'group' => 'seasons', 'date' => ['type' => 'range', 'from' => '03-20', 'to' => '06-20'],
                'primary' => '#357a38', 'secondary' => '#ec407a', 'garland' => 'none', 'ornament' => 'blossom',
                'corner' => 'blossom', 'hat' => 'none', 'trim' => $stripe('#ec407a', '#81c784'), 'effect' => 'petals',
            ],
            'summer' => [
                'group' => 'seasons', 'date' => ['type' => 'range', 'from' => '06-21', 'to' => '09-21'],
                'primary' => '#0277bd', 'secondary' => '#ffb300', 'garland' => 'none', 'ornament' => 'sun',
                'corner' => 'sun', 'hat' => 'none', 'trim' => $stripe('#ffb300', '#0277bd'), 'effect' => 'none',
            ],
            'fall' => [
                'group' => 'seasons', 'date' => ['type' => 'range', 'from' => '09-22', 'to' => '12-20'],
                'primary' => '#b4531a', 'secondary' => '#8d6e63', 'garland' => 'none', 'ornament' => 'leaves',
                'corner' => 'leaves', 'hat' => 'none', 'trim' => $stripe('#c2551b', '#d98a1e'), 'effect' => 'leaves',
            ],
            'winter' => [
                'group' => 'seasons', 'date' => ['type' => 'range', 'from' => '12-21', 'to' => '03-19'],
                'primary' => '#37628a', 'secondary' => '#90a4ae', 'garland' => 'snowflakes', 'ornament' => 'none',
                'corner' => 'flake', 'hat' => 'beanie', 'trim' => 'snow', 'effect' => 'snow',
            ],

            'lunar' => [
                'group' => 'world', 'date' => ['type' => 'chinese', 'month' => 1, 'day' => 1], 'before' => 2, 'after' => 15,
                'primary' => '#c62828', 'secondary' => '#c9a227', 'garland' => 'lanterns', 'ornament' => 'none',
                'corner' => 'lantern', 'hat' => 'none', 'trim' => $stripe('#c62828', '#c9a227'), 'effect' => 'confetti',
            ],
            'diwali' => [
                'group' => 'world', 'date' => ['type' => 'diwali'], 'before' => 2, 'after' => 2,
                'primary' => '#c2410c', 'secondary' => '#e0a100', 'garland' => 'diyas', 'ornament' => 'none',
                'corner' => 'diya', 'hat' => 'none', 'trim' => $stripe('#e0a100', '#c2410c'), 'effect' => 'embers',
            ],
            'hanukkah' => [
                'group' => 'world', 'date' => ['type' => 'hebrew', 'month' => 3, 'day' => 25], 'before' => 1, 'after' => 7,
                'primary' => '#1565c0', 'secondary' => '#90a4ae', 'garland' => 'beads', 'ornament' => 'menorah',
                'corner' => 'dreidel', 'hat' => 'none', 'trim' => $stripe('#1565c0', '#cfd8dc'), 'effect' => 'none',
            ],
            'ramadan' => [
                'group' => 'world', 'date' => ['type' => 'islamic', 'month' => 9, 'day' => 1], 'until' => ['type' => 'islamic', 'month' => 10, 'day' => 1], 'before' => 0, 'after' => 2,
                'primary' => '#00695c', 'secondary' => '#c9a227', 'garland' => 'fanous', 'ornament' => 'crescent',
                'corner' => 'crescent', 'hat' => 'none', 'trim' => $stripe('#c9a227', '#00695c'), 'effect' => 'none',
            ],
        ];
    }

    public static function defaultPriority(string $group): int
    {
        return match ($group) {
            'seasons' => 10,
            'custom' => 30,
            default => 20,
        };
    }

    /** International looks wait for an administrator to switch them on. */
    public static function defaultEnabled(string $group): bool
    {
        return $group !== 'world';
    }

    // What each decoration slot may hold — anything else stored is treated as "none".
    public const GARLANDS = ['none', 'lights', 'hearts', 'shamrocks', 'eggs', 'bunting-usa', 'bunting-halloween', 'leaves', 'snowflakes', 'lanterns', 'diyas', 'beads', 'fanous'];
    public const ORNAMENTS = ['none', 'sparkles', 'bat', 'blossom', 'sun', 'leaves', 'menorah', 'crescent'];
    public const CORNERS = ['none', 'holly', 'web', 'stars', 'sparkles', 'hearts', 'leaves', 'flake', 'lantern', 'diya', 'dreidel', 'crescent', 'shamrock', 'egg', 'sun', 'blossom'];
    public const HATS = ['none', 'santa', 'witch', 'tophat', 'party', 'beanie', 'leprechaun', 'bunny'];
    public const EFFECTS = ['none', 'snow', 'leaves', 'petals', 'hearts', 'embers', 'confetti', 'fireworks'];
}
