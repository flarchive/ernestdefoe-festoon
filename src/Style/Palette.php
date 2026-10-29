<?php

namespace Ernestdefoe\Festoon\Style;

/**
 * A look's two colours, turned into every CSS variable Flarum derives from them.
 *
 * 🚨 Setting --primary-color and --secondary-color alone is not enough. Flarum
 * works out the muted text, control backgrounds, header controls, dark mode body
 * and button shades from those two colours in LESS, at compile time, and writes
 * the results out as literal values. Change only the two inputs at runtime and
 * every derived colour stays on the forum's own palette. So the formulas from
 * core's less/common/variables.less and root.less are repeated here.
 *
 * One deliberate difference: the derived muted colour is darkened (light mode)
 * or lightened (dark mode) until it reaches 4.5:1 on the control background.
 * Core's formula gives 3.6:1 for its own default and as little as 2.8:1 for a
 * gold or yellow secondary, and a holiday look should never be harder to read
 * than the forum it decorates.
 */
class Palette
{
    protected const MIN_CONTRAST = 4.5;

    public function __construct(protected string $primary, protected string $secondary)
    {
    }

    /** The stylesheet body: one block per colour scheme. */
    public function css(): string
    {
        $out = '';

        foreach ($this->blocks() as $selector => $vars) {
            $out .= $selector.'{';
            foreach ($vars as $name => $value) {
                $out .= "--$name:$value;";
            }
            $out .= '}';
        }

        return $out;
    }

    /** @return array<string, array<string, string>> */
    public function blocks(): array
    {
        $p = Color::parse($this->primary);
        $s = Color::parse($this->secondary);
        [$h, $sat] = [$s->hue(), $s->saturation()];

        $white = Color::parse('#ffffff');
        $bodyDark = Color::hsl($h, min(20, $sat), 10);

        $controlBgLight = Color::hsl($h, min(50, $sat), 93);
        $controlBgDark = Color::hsl($h, min(20, $sat), 13);
        $mutedLight = self::readable(Color::hsl($h, min(20, $sat), 50), $controlBgLight, -1);
        $mutedDark = self::readable(Color::hsl($h, min(15, $sat), 50), $controlBgDark, 1);
        $mutedMoreDark = Color::hsl($h, min(10, $sat), 40);

        // Same YIQ test core uses to pick the primary button's text colour.
        $onPrimary = $p->yiq() >= 128 ? $bodyDark : $white;

        $root = [
            'primary-color' => $p->hex(),
            'secondary-color' => $s->hex(),
            'link-color' => $p->saturate(10)->hex(),
            'text-on-light' => $bodyDark->hex(),
        ] + self::button($onPrimary, $p, 'button-primary');

        $light = [
            'muted-color' => $mutedLight->hex(),
            'muted-color-light' => $mutedLight->lighten(10)->hex(),
            'muted-color-dark' => $mutedLight->darken(50)->hex(),
            'control-bg' => $controlBgLight->hex(),
            'control-bg-light' => $controlBgLight->lighten(3)->hex(),
            'control-bg-shaded' => $controlBgLight->darken(4)->hex(),
            'control-color' => $mutedLight->hex(),
            'control-body-bg-mix' => $controlBgLight->mix($white, 50)->hex(),
            'control-muted-color' => $mutedLight->lighten(40)->hex(),
            'header-color' => $p->hex(),
            'header-control-bg' => $controlBgLight->hex(),
            'header-control-color' => $mutedLight->hex(),
            'overlay-bg' => $s->rgba(0.9),
        ] + self::button($mutedLight, $controlBgLight, 'button')
          + self::button($mutedLight, $white, 'button-inverted');

        $dark = [
            'body-bg' => $bodyDark->hex(),
            'body-bg-shaded' => $bodyDark->darken(3)->hex(),
            'body-bg-light' => $bodyDark->lighten(5)->hex(),
            'body-bg-faded' => $bodyDark->rgba(0.93),
            'muted-color' => $mutedDark->hex(),
            'muted-color-light' => $mutedDark->lighten(10)->hex(),
            'muted-color-dark' => $mutedDark->darken(50)->hex(),
            'muted-more-color' => $mutedMoreDark->hex(),
            'control-bg' => $controlBgDark->hex(),
            'control-bg-light' => $controlBgDark->lighten(3)->hex(),
            'control-bg-shaded' => $controlBgDark->darken(4)->hex(),
            'control-color' => $mutedDark->hex(),
            'control-body-bg-mix' => $controlBgDark->mix($bodyDark, 50)->hex(),
            'control-muted-color' => $mutedDark->darken(25)->hex(),
            'header-bg' => $bodyDark->hex(),
            'header-color' => $p->hex(),
            'header-control-bg' => $controlBgDark->hex(),
            'header-control-color' => $mutedDark->hex(),
            'overlay-bg' => $bodyDark->darken(5)->rgba(0.9),
            'code-bg' => $bodyDark->darken(3)->hex(),
            'discussion-title-color' => Color::parse('#dddddd')->mix($bodyDark, 55)->hex(),
        ] + self::button($mutedDark, $controlBgDark, 'button')
          + self::button($mutedDark, $bodyDark, 'button-inverted');

        $coloredLight = self::coloredHeader($p, $white);
        $coloredDark = self::coloredHeader($p, $bodyDark);

        // High contrast modes start from the same derived colours and push them
        // further; without these blocks the light/dark ones above would undo them.
        $lightHc = self::highContrast($mutedLight->darken(20), $controlBgLight, $white, -1);
        $darkHc = self::highContrast($mutedDark->lighten(20), $controlBgDark, $bodyDark, 1);

        return [
            ':root' => $root,
            '[data-theme^=light]' => $light,
            '[data-theme^=light][data-colored-header=true]' => $coloredLight,
            '[data-theme^=dark]' => $dark,
            '[data-theme^=dark][data-colored-header=true]' => $coloredDark,
            '[data-theme=light-hc]' => $lightHc,
            '[data-theme=dark-hc]' => $darkHc,
        ];
    }

    /**
     * Step the colour away from its background, one point of lightness at a
     * time, until it is readable. $direction -1 darkens, 1 lightens.
     */
    public static function readable(Color $color, Color $on, int $direction): Color
    {
        for ($i = 0; $i < 50 && $color->contrast($on) < self::MIN_CONTRAST; $i++) {
            $color = $direction < 0 ? $color->darken(1) : $color->lighten(1);
        }

        return $color;
    }

    /** @return array<string, string> */
    protected static function button(Color $color, Color $bg, string $name): array
    {
        return [
            "$name-color" => $color->hex(),
            "$name-bg" => $bg->hex(),
            "$name-bg-hover" => $bg->darken(5)->hex(),
            "$name-bg-active" => $bg->darken(10)->hex(),
            "$name-bg-disabled" => $bg->hex(),
        ];
    }

    /** @return array<string, string> */
    protected static function coloredHeader(Color $primary, Color $body): array
    {
        return [
            'header-bg' => $primary->hex(),
            'header-color' => $body->hex(),
            'header-control-bg' => Color::parse('#000000')->mix($primary, 10)->hex(),
            'header-control-color' => $body->mix($primary, 60)->hex(),
        ];
    }

    /** @return array<string, string> */
    protected static function highContrast(Color $muted, Color $controlBg, Color $body, int $direction): array
    {
        return [
            'control-color' => $muted->hex(),
            'control-muted-color' => ($direction < 0 ? $muted->lighten(20) : $muted->darken(20))->hex(),
            'muted-color' => $muted->hex(),
            'muted-color-light' => ($direction < 0 ? $muted->lighten(10) : $muted->darken(10))->hex(),
            'muted-color-dark' => ($direction < 0 ? $muted->darken(50) : $muted->lighten(50))->hex(),
        ] + self::button($muted, $controlBg, 'button')
          + self::button($muted, $body, 'button-inverted');
    }
}
