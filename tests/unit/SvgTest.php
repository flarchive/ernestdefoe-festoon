<?php

namespace Ernestdefoe\Festoon\Tests\unit;

use Ernestdefoe\Festoon\Config;
use Ernestdefoe\Festoon\Style\Decorations;
use Ernestdefoe\Festoon\Style\Svg;
use PHPUnit\Framework\TestCase;

class SvgTest extends TestCase
{
    public function test_drawing_is_kept_and_currentcolor_takes_the_main_color(): void
    {
        $uri = Svg::uri('<svg viewBox="0 0 10 10"><circle cx="5" cy="5" r="4" fill="currentColor"/></svg>', '#283593');
        $svg = rawurldecode(substr($uri, strlen('data:image/svg+xml,')));

        $this->assertStringContainsString('xmlns="http://www.w3.org/2000/svg"', $svg);
        $this->assertStringContainsString('<circle cx="5" cy="5" r="4" fill="#283593"', $svg);
    }

    public function test_everything_that_could_run_or_load_is_removed(): void
    {
        $svg = Svg::clean('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" onload="alert(1)">'
            .'<script>alert(2)</script><foreignObject><iframe src="https://x"/></foreignObject><!-- c -->'
            .'<a href="javascript:alert(3)"><rect width="1" height="1"/></a>'
            .'<image href="https://evil.example/x.png"/><style>@import url(https://x)</style>'
            .'<use xlink:href="https://evil.example/s.svg#a"/><use href="#ok"/>'
            .'<rect fill="url(https://evil.example/p)" stroke="url(#grad)" style="fill:url(//x)" onclick="x"/>'
            .'<set attributeName="href" to="javascript:alert(4)"/><animate attributeName="x"/>'
            .'<path d="M0 0" fill="java&#x0A;script:x"/></svg>');

        $this->assertNotNull($svg);
        foreach (['onload', 'onclick', 'script', 'alert', 'foreignObject', 'iframe', 'evil', '<a', '<image', '<style', '<set', '<animate', 'url(//', '<!--', 'url(https'] as $bad) {
            $this->assertStringNotContainsStringIgnoringCase($bad, $svg, $bad);
        }
        $this->assertStringContainsString('stroke="url(#grad)"', $svg);
        $this->assertStringContainsString('href="#ok"', $svg);
    }

    public function test_doctypes_entities_and_non_svg_are_refused(): void
    {
        $this->assertNull(Svg::clean('<!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]><svg>&x;</svg>'));
        $this->assertNull(Svg::clean('<html><body>hi</body></html>'));
        $this->assertNull(Svg::clean('not xml'));
        $this->assertNull(Svg::clean(''));
        $this->assertNull(Svg::clean('<svg>'.str_repeat(' ', Svg::MAX).'</svg>'));
    }

    public function test_the_picture_cannot_break_out_of_the_stylesheet(): void
    {
        $config = new Config(new ArraySettings([
            Config::CUSTOM => json_encode([['id' => 'x', 'name' => 'Day', 'ornament' => 'svg', 'corner' => 'svg', 'size' => 48,
                'svg' => '<svg><text>")} &lt;/style&gt; {</text><script>alert(1)</script></svg>']]),
        ]));
        $css = Decorations::css($config->look('custom-x'), $config->parts());

        preg_match_all('/url\("([^"]*)"\)/', $css, $m);
        $this->assertNotEmpty($m[1]);
        foreach ($m[1] as $uri) {
            $this->assertMatchesRegularExpression('/^data:image\/svg\+xml,[A-Za-z0-9%._~-]+$/', $uri);
        }
        $this->assertStringNotContainsString('</style>', $css);
        $this->assertStringContainsString('.Header-title::after{content:"";flex:none;margin-inline-start:6px;pointer-events:none;width:48px;height:48px', $css);
    }

    public function test_a_fixed_date_holiday_can_move_and_others_cannot(): void
    {
        $config = new Config(new ArraySettings([
            Config::LOOKS => json_encode(['christmas' => ['md' => '12-24'], 'easter' => ['md' => '12-24'], 'spring' => ['md' => 'x']]),
        ]));

        $this->assertSame(['type' => 'date', 'md' => '12-24'], $config->look('christmas')->date());
        $this->assertSame(['type' => 'easter'], $config->look('easter')->date());
    }
}
