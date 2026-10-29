<?php

namespace Ernestdefoe\Festoon\Tests\unit;

use DateTimeImmutable;
use Ernestdefoe\Festoon\Config;
use Ernestdefoe\Festoon\Look\Schedule;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ScheduleTest extends TestCase
{
    private function schedule(array $settings = []): Schedule
    {
        return new Schedule(new Config(new ArraySettings(['ernestdefoe-festoon.timezone' => 'America/New_York'] + $settings)));
    }

    public static function days(): array
    {
        return [
            'Christmas beats Winter' => ['2026-12-22', 'christmas'],
            'Winter after Christmas' => ['2026-12-28', 'winter'],
            'New Year wraps the year' => ['2027-01-01', 'newyear'],
            'Winter runs into March' => ['2027-03-01', 'winter'],
            'Easter week' => ['2027-03-25', 'easter'],
            'Spring after Easter' => ['2027-04-05', 'spring'],
            'Halloween' => ['2026-10-29', 'halloween'],
            'Fall before Halloween opens' => ['2026-10-01', 'fall'],
            'Thanksgiving weekend' => ['2026-11-29', 'thanksgiving'],
            'Lunar New Year is off by default' => ['2027-02-06', 'winter'],
        ];
    }

    #[DataProvider('days')]
    public function test_the_right_look_wins(string $day, string $expected): void
    {
        $tz = new \DateTimeZone('America/New_York');

        $this->assertSame($expected, $this->schedule()->active(new DateTimeImmutable($day.' 12:00', $tz))?->id);
    }

    public function test_an_international_look_once_turned_on(): void
    {
        $schedule = $this->schedule(['ernestdefoe-festoon.looks' => json_encode(['lunar' => ['enabled' => true]])]);

        $this->assertSame('lunar', $schedule->active(new DateTimeImmutable('2027-02-06 12:00', new \DateTimeZone('America/New_York')))?->id);
    }

    public function test_a_custom_look_beats_everything_on_its_days(): void
    {
        $schedule = $this->schedule(['ernestdefoe-festoon.custom' => json_encode([
            ['id' => 'founders', 'name' => 'Founders Day', 'enabled' => true, 'from' => '12-24', 'to' => '12-24', 'primary' => '#000000', 'secondary' => '#ffffff'],
        ])]);
        $tz = new \DateTimeZone('America/New_York');

        $this->assertSame('custom-founders', $schedule->active(new DateTimeImmutable('2026-12-24 12:00', $tz))?->id);
        $this->assertSame('christmas', $schedule->active(new DateTimeImmutable('2026-12-23 12:00', $tz))?->id);
    }

    public function test_bad_stored_values_are_cleaned(): void
    {
        $config = new Config(new ArraySettings([
            'ernestdefoe-festoon.looks' => json_encode(['christmas' => ['primary' => 'red}', 'hat' => '<script>', 'trim' => ['#fff', 'x'], 'priority' => 9999]]),
        ]));
        $look = $config->look('christmas');

        $this->assertSame('#b71c1c', $look->get('primary'));
        $this->assertSame('none', $look->get('hat'));
        $this->assertSame('none', $look->trim());
        $this->assertSame(100, $look->priority());
    }
}
