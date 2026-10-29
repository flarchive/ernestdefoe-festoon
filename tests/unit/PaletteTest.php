<?php

namespace Ernestdefoe\Festoon\Tests\unit;

use Ernestdefoe\Festoon\Look\Catalogue;
use Ernestdefoe\Festoon\Style\Color;
use Ernestdefoe\Festoon\Style\Palette;
use PHPUnit\Framework\TestCase;

class PaletteTest extends TestCase
{
    /** Flarum's own default colours must come out as core's own compiled values. */
    public function test_core_default_matches_core(): void
    {
        // Values from a stock forum's compiled forum.css with #4D698E.
        $blocks = (new Palette('#4D698E', '#4D698E'))->blocks();

        $this->assertSame('#4d698e', $blocks[':root']['primary-color']);
        $this->assertSame('#426799', $blocks[':root']['link-color']);
        $this->assertSame('#445d7d', $blocks[':root']['button-primary-bg-hover']);
        $this->assertSame('#e8ecf2', $blocks['[data-theme^=light]']['control-bg']);
        $this->assertSame('#dbe2eb', $blocks['[data-theme^=light]']['control-bg-shaded']);
        $this->assertSame('#14191f', $blocks['[data-theme^=dark]']['body-bg']);
    }

    /** No look may leave muted text harder to read than 4.5:1, in light or dark mode. */
    public function test_every_look_keeps_muted_text_readable(): void
    {
        foreach (Catalogue::all() as $id => $look) {
            $blocks = (new Palette($look['primary'], $look['secondary']))->blocks();

            foreach (['[data-theme^=light]', '[data-theme^=dark]'] as $scheme) {
                $ratio = Color::parse($blocks[$scheme]['muted-color'])->contrast(Color::parse($blocks[$scheme]['control-bg']));
                $this->assertGreaterThanOrEqual(4.5, round($ratio, 2), "$id $scheme");
            }
        }
    }

    /** White text on every look's primary button must be readable too. */
    public function test_primary_buttons_are_readable(): void
    {
        foreach (Catalogue::all() as $id => $look) {
            $blocks = (new Palette($look['primary'], $look['secondary']))->blocks()[':root'];
            $ratio = Color::parse($blocks['button-primary-color'])->contrast(Color::parse($blocks['button-primary-bg']));
            $this->assertGreaterThanOrEqual(4.5, round($ratio, 2), $id);
        }
    }

    public function test_bad_input_falls_back_instead_of_breaking_css(): void
    {
        $css = (new Palette('red;}body{display:none', '#zzzzzz'))->css();

        $this->assertStringNotContainsString('display:none', $css);
        $this->assertStringContainsString('--primary-color:#536f90', $css);
    }
}
