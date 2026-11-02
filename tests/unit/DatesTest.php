<?php

namespace Ernestdefoe\Festoon\Tests\unit;

use DateTimeZone;
use Ernestdefoe\Festoon\Calendar\Dates;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Every moving date checked against the published one. Several of these are
 * the awkward years: 2020's extra lunar month before Diwali, 2024's Diwali
 * across two evenings, and 2027's Lunar New Year, whose new moon is four
 * minutes before midnight in China.
 */
class DatesTest extends TestCase
{
    public static function known(): array
    {
        $cases = [];
        $add = function (string $label, array $rule, array $years) use (&$cases) {
            foreach ($years as $year => $date) {
                $cases["$label $year"] = [$rule, $year, $date];
            }
        };

        $add('Easter', ['type' => 'easter'], [2024 => '2024-03-31', 2025 => '2025-04-20', 2026 => '2026-04-05', 2027 => '2027-03-28', 2038 => '2038-04-25']);
        $add('Thanksgiving', ['type' => 'weekday', 'month' => 11, 'weekday' => 4, 'nth' => 4], [2024 => '2024-11-28', 2025 => '2025-11-27', 2026 => '2026-11-26']);
        $add('Memorial Day (last Monday)', ['type' => 'weekday', 'month' => 5, 'weekday' => 1, 'nth' => -1], [2025 => '2025-05-26', 2026 => '2026-05-25']);
        $add('Diwali', ['type' => 'diwali'], [2019 => '2019-10-27', 2020 => '2020-11-14', 2021 => '2021-11-04', 2022 => '2022-10-24', 2023 => '2023-11-12', 2024 => '2024-10-31', 2025 => '2025-10-20', 2026 => '2026-11-08', 2027 => '2027-10-29', 2028 => '2028-10-17']);
        $add('Lunar New Year', ['type' => 'chinese', 'month' => 1, 'day' => 1], [2020 => '2020-01-25', 2023 => '2023-01-22', 2024 => '2024-02-10', 2025 => '2025-01-29', 2026 => '2026-02-17', 2027 => '2027-02-06', 2033 => '2033-01-31', 2034 => '2034-02-19', 2045 => '2045-02-17', 2050 => '2050-01-23']);
        $add('Hanukkah', ['type' => 'hebrew', 'month' => 3, 'day' => 25], [2023 => '2023-12-08', 2024 => '2024-12-26', 2025 => '2025-12-15', 2026 => '2026-12-05']);
        $add('Ramadan', ['type' => 'islamic', 'month' => 9, 'day' => 1], [2024 => '2024-03-11', 2025 => '2025-03-01', 2026 => '2026-02-18']);
        $add('Eid al-Fitr', ['type' => 'islamic', 'month' => 10, 'day' => 1], [2024 => '2024-04-10', 2025 => '2025-03-30', 2026 => '2026-03-20']);
        $add('Leap day in a common year', ['type' => 'date', 'md' => '02-29'], [2026 => '2026-02-28', 2028 => '2028-02-29']);

        return $cases;
    }

    #[DataProvider('known')]
    public function test_date(array $rule, int $year, string $expected): void
    {
        $dates = new Dates(new DateTimeZone('UTC'));

        $this->assertSame($expected, $dates->on($rule, $year)?->format('Y-m-d'));
    }

    public function test_an_unknown_rule_has_no_date(): void
    {
        $dates = new Dates(new DateTimeZone('UTC'));

        $this->assertNull($dates->on(['type' => 'nope'], 2026));
        $this->assertNull($dates->on(['type' => 'weekday', 'month' => 2, 'weekday' => 1, 'nth' => 5], 2026));
    }
}
