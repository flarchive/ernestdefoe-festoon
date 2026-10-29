<?php

namespace Ernestdefoe\Festoon\Look;

use DateTimeImmutable;
use DateTimeZone;
use Ernestdefoe\Festoon\Calendar\Dates;
use Ernestdefoe\Festoon\Config;

/**
 * Which look the forum wears on a given day.
 *
 * Every enabled look whose window contains the day is a candidate; the
 * highest priority wins, and between equals the shorter window wins. That
 * is how Christmas sits on top of Winter without either knowing about the
 * other, and how a one-day holiday of the administrator's own beats both.
 */
class Schedule
{
    protected DateTimeZone $tz;
    protected Dates $dates;

    public function __construct(protected Config $config)
    {
        $this->tz = new DateTimeZone($config->timezone());
        $this->dates = new Dates($this->tz);
    }

    public function today(): DateTimeImmutable
    {
        return new DateTimeImmutable('today', $this->tz);
    }

    public function active(?DateTimeImmutable $day = null): ?Look
    {
        $day = ($day ?? $this->today())->setTimezone($this->tz)->setTime(0, 0);
        $best = null;
        $bestLength = PHP_INT_MAX;

        foreach ($this->config->looks() as $look) {
            if (! $look->enabled()) {
                continue;
            }

            $window = $this->windowContaining($look, $day);
            if ($window === null) {
                continue;
            }

            $length = (int) $window[0]->diff($window[1])->days;

            if ($best === null || $look->priority() > $best->priority()
                || ($look->priority() === $best->priority() && $length < $bestLength)) {
                $best = $look;
                $bestLength = $length;
            }
        }

        return $best;
    }

    /**
     * The look's window that contains $day, if one does. The years either side
     * are tried too: a window opening in December for a January date, or one
     * running into January from a December date, belongs to the next or the
     * previous year's occurrence.
     *
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}|null
     */
    public function windowContaining(Look $look, DateTimeImmutable $day): ?array
    {
        $year = (int) $day->format('Y');

        foreach ([$year, $year + 1, $year - 1] as $y) {
            $window = $this->window($look, $y);

            if ($window !== null && $day >= $window[0] && $day <= $window[1]) {
                return $window;
            }
        }

        return null;
    }

    /**
     * The window that opens for the occurrence in $year.
     *
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}|null
     */
    public function window(Look $look, int $year): ?array
    {
        $rule = $look->date();

        if (($rule['type'] ?? '') === 'range') {
            $from = Dates::monthDay((string) $rule['from'], $year);
            $to = Dates::monthDay((string) $rule['to'], $year);
            if ($from === null || $to === null) {
                return null;
            }

            $start = new DateTimeImmutable($from, $this->tz);
            $end = new DateTimeImmutable($to, $this->tz);

            // A range like 12-21 → 03-19 wraps into the next year.
            return [$start, $end < $start ? $end->modify('+1 year') : $end];
        }

        $anchor = $this->dates->on($rule, $year);
        if ($anchor === null) {
            return null;
        }

        $end = $anchor;
        if ($until = $look->until()) {
            $end = $this->dates->on($until, $year);
            if ($end === null || $end < $anchor) {
                $end = $this->dates->on($until, $year + 1);
            }
            if ($end === null) {
                return null;
            }
        }

        return [$anchor->modify('-'.$look->before().' days'), $end->modify('+'.$look->after().' days')];
    }

    /**
     * When each enabled look next runs, for the admin page's calendar.
     *
     * @return array<string, array{0: string, 1: string}|null>
     */
    public function nextWindows(): array
    {
        $today = $this->today();
        $year = (int) $today->format('Y');
        $out = [];

        foreach ($this->config->looks() as $id => $look) {
            $out[$id] = null;

            foreach ([$year - 1, $year, $year + 1] as $y) {
                $w = $this->window($look, $y);
                if ($w !== null && $w[1] >= $today) {
                    $out[$id] = [$w[0]->format('Y-m-d'), $w[1]->format('Y-m-d')];
                    break;
                }
            }
        }

        return $out;
    }
}
