<?php

namespace Ernestdefoe\Festoon\Tests\unit;

use DateTimeZone;
use Ernestdefoe\Festoon\Calendar\Dates;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\TestCase;

/** The world calendars are worked out once and then read from the cache store. */
class DatesCacheTest extends TestCase
{
    private function dates(Repository $cache): Dates
    {
        return new class(new DateTimeZone('UTC'), $cache) extends Dates {
            public int $computed = 0;

            protected function compute(array $rule, int $year): ?string
            {
                $this->computed++;

                return parent::compute($rule, $year);
            }
        };
    }

    public function test_a_world_date_is_worked_out_once_across_requests(): void
    {
        $cache = new Repository(new ArrayStore());
        $hanukkah = ['type' => 'hebrew', 'month' => 3, 'day' => 25];

        $first = $this->dates($cache);
        $this->assertSame('2026-12-05', $first->on($hanukkah, 2026)?->format('Y-m-d'));
        $this->assertSame(1, $first->computed);

        $next = $this->dates($cache);
        $this->assertSame('2026-12-05', $next->on($hanukkah, 2026)?->format('Y-m-d'));
        $this->assertSame(0, $next->computed);
    }

    public function test_a_year_with_no_such_date_is_remembered_too(): void
    {
        $cache = new Repository(new ArrayStore());
        $rule = ['type' => 'chinese', 'month' => 13, 'day' => 1];

        $this->assertNull($this->dates($cache)->on($rule, 2026));
        $next = $this->dates($cache);
        $this->assertNull($next->on($rule, 2026));
        $this->assertSame(0, $next->computed);
    }

    public function test_plain_dates_never_touch_the_cache_store(): void
    {
        $store = new ArrayStore();
        $dates = $this->dates(new Repository($store));

        $this->assertSame('2026-04-05', $dates->on(['type' => 'easter'], 2026)?->format('Y-m-d'));
        $this->assertSame('2026-12-25', $dates->on(['type' => 'date', 'md' => '12-25'], 2026)?->format('Y-m-d'));
        $this->assertSame([], $store->all());
    }
}
