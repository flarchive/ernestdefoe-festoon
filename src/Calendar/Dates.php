<?php

namespace Ernestdefoe\Festoon\Calendar;

use DateTimeImmutable;
use DateTimeZone;
use IntlCalendar;

/**
 * The Gregorian date a holiday falls on in a given year.
 *
 * A rule is a small array, so looks can be stored as JSON:
 *   ['type' => 'date', 'md' => '12-25']                      every year on that day
 *   ['type' => 'easter']                                     Western Easter Sunday
 *   ['type' => 'weekday', 'month' => 11, 'weekday' => 4, 'nth' => 4]   4th Thursday of November (nth -1 = last)
 *   ['type' => 'chinese', 'month' => 1, 'day' => 1]         a day in the Chinese calendar
 *   ['type' => 'hebrew', 'month' => 3, 'day' => 25]         Hebrew calendar, months counted from Tishrei = 1
 *   ['type' => 'islamic', 'month' => 9, 'day' => 1]         Islamic calendar (Umm al-Qura), Muharram = 1
 *   ['type' => 'diwali']                                     Lakshmi Puja, from the moon
 *
 * The Chinese, Hebrew and Islamic dates come from ICU (ext-intl), which carries
 * those calendars properly — leap months included — rather than a table that
 * runs out. Nothing here is tied to a particular year.
 */
class Dates
{
    /** @var array<string, ?string> "type|params|year" => "Y-m-d" */
    protected array $memo = [];

    public function __construct(protected DateTimeZone $tz)
    {
    }

    /** @param array<string, mixed> $rule */
    public function on(array $rule, int $year): ?DateTimeImmutable
    {
        $key = json_encode($rule).'|'.$year;

        if (! array_key_exists($key, $this->memo)) {
            $this->memo[$key] = $this->compute($rule, $year);
        }

        $ymd = $this->memo[$key];

        return $ymd === null ? null : new DateTimeImmutable($ymd, $this->tz);
    }

    /** @param array<string, mixed> $rule */
    protected function compute(array $rule, int $year): ?string
    {
        $int = fn (string $k, int $default = 0) => (int) ($rule[$k] ?? $default);

        return match ($rule['type'] ?? null) {
            'date' => self::monthDay((string) ($rule['md'] ?? ''), $year),
            'easter' => self::easter($year),
            'weekday' => self::nthWeekday($year, $int('month', 1), $int('weekday', 1), $int('nth', 1)),
            'chinese' => $this->chinese($year, $int('month', 1), $int('day', 1)),
            'hebrew' => $this->icu('hebrew', $year, $int('month', 1) - 1, $int('day', 1)),
            'islamic' => $this->icu('islamic-umalqura', $year, $int('month', 1) - 1, $int('day', 1)),
            'diwali' => self::diwali($year),
            default => null,
        };
    }

    public static function monthDay(string $md, int $year): ?string
    {
        if (! preg_match('/^(\d{2})-(\d{2})$/', $md, $m) || ! checkdate((int) $m[1], (int) $m[2], $year)) {
            // 02-29 in a common year falls back to the 28th rather than vanishing
            return $md === '02-29' ? "$year-02-28" : null;
        }

        return "$year-$md";
    }

    /** Western Easter Sunday — the anonymous Gregorian algorithm (Meeus/Jones/Butcher). */
    public static function easter(int $year): string
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $h = (19 * $a + $b - intdiv($b, 4) - intdiv($b - intdiv($b + 8, 25) + 1, 3) + 15) % 30;
        $l = (32 + 2 * ($b % 4) + 2 * intdiv($c, 4) - $h - $c % 4) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $n = $h + $l - 7 * $m + 114;

        return sprintf('%d-%02d-%02d', $year, intdiv($n, 31), $n % 31 + 1);
    }

    /** The nth ISO weekday (1 = Monday … 7 = Sunday) of a month; nth -1 is the last. */
    public static function nthWeekday(int $year, int $month, int $weekday, int $nth): ?string
    {
        if ($month < 1 || $month > 12 || $weekday < 1 || $weekday > 7 || $nth === 0 || $nth < -1 || $nth > 5) {
            return null;
        }

        $days = (int) gmdate('t', gmmktime(0, 0, 0, $month, 1, $year));

        if ($nth === -1) {
            $lastDow = (int) date('N', gmmktime(0, 0, 0, $month, $days, $year));
            $day = $days - ($lastDow - $weekday + 7) % 7;
        } else {
            $firstDow = (int) date('N', gmmktime(0, 0, 0, $month, 1, $year));
            $day = 1 + ($weekday - $firstDow + 7) % 7 + ($nth - 1) * 7;
        }

        return $day <= $days ? sprintf('%d-%02d-%02d', $year, $month, $day) : null;
    }

    /**
     * Diwali (Lakshmi Puja): the evening that falls in the amavasya tithi — the
     * last twelve degrees before the moon catches the sun — of the new moon that
     * ends Ashvin, which is the one with the sun in sidereal Libra (Tula).
     *
     * Evenings are taken at 18:00 India time, when the puja is done. When the
     * tithi covers two evenings the first one is the day, which is how the 2024
     * October 31 / November 1 question was settled by most calendars.
     */
    public static function diwali(int $year): ?string
    {
        $ist = new DateTimeZone('Asia/Kolkata');
        $day = new DateTimeImmutable("$year-10-01 18:00", $ist);

        for ($i = 0; $i < 61; $i++, $day = $day->modify('+1 day')) {
            $ts = $day->getTimestamp();

            if (Astronomy::elongation($ts) < 348) {
                continue;
            }

            // The month is named by where the sun stands at the new moon itself,
            // not on the evening before. In 2020 the sun reached Libra six hours
            // after the October new moon, and that month was an extra Ashvin.
            $conjunction = Astronomy::newMoonAfter($ts);
            $sidereal = Astronomy::wrap(Astronomy::sun(Astronomy::centuries($conjunction)) - Astronomy::ayanamsa($conjunction));

            if ($sidereal >= 180 && $sidereal < 210) {
                return $day->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * A Chinese calendar date. ICU knows which new moon starts each month,
     * leap months included, but its moon is approximate: for 2027 it puts New
     * Year on February 7, and the new moon was at 23:56 on the 6th, China time.
     * So ICU picks the month and the moon here picks the day it starts on.
     */
    protected function chinese(int $year, int $month, int $day): ?string
    {
        $approx = $this->icu('chinese', $year, $month - 1, 1);
        if ($approx === null) {
            return null;
        }

        $china = new DateTimeZone('Asia/Shanghai');
        $newMoon = Astronomy::newMoonAfter((new DateTimeImmutable("$approx 00:00", $china))->modify('-3 days')->getTimestamp());
        $first = (new DateTimeImmutable('@'.$newMoon))->setTimezone($china)->setTime(0, 0);
        $date = $first->modify('+'.($day - 1).' days');

        return $date->format('Y') === (string) $year ? $date->format('Y-m-d') : null;
    }

    /**
     * The Gregorian day in $year on which a non-Gregorian calendar reads
     * month/day (0-based month). The year is walked a day at a time: ICU
     * cannot be asked "when is Kislev 25 in 2026" directly because the Hebrew
     * and Islamic years do not line up with the Gregorian one, and a walk also
     * skips Chinese leap months for free.
     */
    protected function icu(string $calendar, int $year, int $month, int $day): ?string
    {
        if (! class_exists(IntlCalendar::class)) {
            return null;
        }

        $cal = IntlCalendar::createInstance('UTC', "en_US@calendar=$calendar");
        if ($cal === null) {
            return null;
        }

        $date = new DateTimeImmutable("$year-01-01 12:00", new DateTimeZone('UTC'));
        $end = (int) $date->modify('+1 year')->format('U');

        for ($ts = (int) $date->format('U'); $ts < $end; $ts += 86400) {
            $cal->setTime($ts * 1000.0);

            if ($cal->get(IntlCalendar::FIELD_MONTH) === $month
                && $cal->get(IntlCalendar::FIELD_DAY_OF_MONTH) === $day
                && ($calendar !== 'chinese' || $cal->get(IntlCalendar::FIELD_IS_LEAP_MONTH) === 0)) {
                return gmdate('Y-m-d', $ts);
            }
        }

        return null;
    }
}

