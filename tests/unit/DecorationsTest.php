<?php

namespace Ernestdefoe\Festoon\Tests\unit;

use Ernestdefoe\Festoon\Config;
use Ernestdefoe\Festoon\Content\InjectFestoon;
use Ernestdefoe\Festoon\Style\Decorations;

use PHPUnit\Framework\TestCase;

class DecorationsTest extends TestCase
{
    public function test_every_look_builds_and_every_piece_has_a_still_version(): void
    {
        $config = new Config(new ArraySettings([]));

        foreach ($config->looks() as $id => $look) {
            $css = Decorations::css($look, $config->parts());

            if ($look->get('effect') !== 'none' || $look->get('garland') !== 'none') {
                $this->assertStringContainsString('prefers-reduced-motion:reduce', $css, $id);
            }
            // Every selector that decorates something is behind the member's own switch.
            preg_match_all('/(?:^|[{}])([^{}@]+)\{/', $css, $m);
            foreach ($m[1] as $list) {
                foreach (explode(',', $list) as $selector) {
                    if (preg_match('/(App-header|App-navigation|WelcomeHero|Post-side|PostUser-name|Bespoke-widget|FofWidgets|PostStream-item|Header-title|newDiscussion|SplitDropdown)/', $selector)) {
                        $this->assertStringContainsString(':not(.Festoon-off)', $selector, "$id: $selector");
                    }
                }
            }
            $this->assertLessThan(4096, strlen(gzencode($css, 6)), "$id stylesheet, gzipped");
        }
    }

    public function test_switched_off_parts_are_left_out(): void
    {
        $config = new Config(new ArraySettings([]));
        $css = Decorations::css($config->look('christmas'), ['colors' => true, 'header' => false, 'effects' => false, 'hats' => false, 'ornaments' => false, 'trims' => false]);

        $this->assertStringNotContainsString('App-header', $css);
        $this->assertStringNotContainsString('Post-side', $css);
        $this->assertStringNotContainsString('Festoon-fall', $css);
    }

    public function test_falling_layer_only_for_falling_effects(): void
    {
        $this->assertStringContainsString('Festoon-fall--snow', InjectFestoon::falling('snow'));
        $this->assertSame('', InjectFestoon::falling('fireworks'));
        $this->assertSame('', InjectFestoon::falling('none'));
    }
}
