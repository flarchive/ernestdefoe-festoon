<?php

namespace Ernestdefoe\Festoon\Style;

/**
 * An opaque colour with the handful of LESS colour functions Flarum's theme
 * formulas use, worked the way LESS works them: in HSL, percentages as points.
 */
final class Color
{
    /** @param float $h 0–360 @param float $s 0–100 @param float $l 0–100 */
    private function __construct(private float $h, private float $s, private float $l)
    {
    }

    public static function parse(string $hex): self
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (! preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            $hex = '536f90'; // Flarum's own default, rather than a broken stylesheet
        }

        [$r, $g, $b] = array_map(fn ($c) => hexdec($c) / 255, str_split($hex, 2));
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        $h = $s = 0.0;

        if ($max !== $min) {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            $h = match ($max) {
                $r => ($g - $b) / $d + ($g < $b ? 6 : 0),
                $g => ($b - $r) / $d + 2,
                default => ($r - $g) / $d + 4,
            } * 60;
        }

        return new self($h, $s * 100, $l * 100);
    }

    public static function hsl(float $h, float $s, float $l): self
    {
        return new self(fmod($h + 360, 360), self::clamp($s), self::clamp($l));
    }

    public function hue(): float
    {
        return $this->h;
    }

    public function saturation(): float
    {
        return $this->s;
    }

    public function lighten(float $points): self
    {
        return self::hsl($this->h, $this->s, $this->l + $points);
    }

    public function darken(float $points): self
    {
        return self::hsl($this->h, $this->s, $this->l - $points);
    }

    public function saturate(float $points): self
    {
        return self::hsl($this->h, $this->s + $points, $this->l);
    }

    /** LESS mix(): $weight percent of this colour, the rest of $other. */
    public function mix(self $other, float $weight): self
    {
        $w = $weight / 100;
        $a = $this->rgb();
        $b = $other->rgb();

        return self::parse(sprintf('%02x%02x%02x', ...array_map(
            fn ($i) => (int) round($a[$i] * $w + $b[$i] * (1 - $w)),
            [0, 1, 2]
        )));
    }

    /** @return array{0: int, 1: int, 2: int} */
    public function rgb(): array
    {
        $s = $this->s / 100;
        $l = $this->l / 100;
        $a = $s * min($l, 1 - $l);
        $f = function (int $n) use ($l, $a) {
            $k = fmod($n + $this->h / 30, 12);

            return (int) round(255 * ($l - $a * max(-1, min($k - 3, 9 - $k, 1))));
        };

        return [$f(0), $f(8), $f(4)];
    }

    public function hex(): string
    {
        return vsprintf('#%02x%02x%02x', $this->rgb());
    }

    public function rgba(float $alpha): string
    {
        return vsprintf('rgba(%d,%d,%d,', $this->rgb()).rtrim(rtrim(number_format($alpha, 2, '.', ''), '0'), '.').')';
    }

    /** 0–255, the YIQ brightness core and its JS isDark() use. */
    public function yiq(): float
    {
        [$r, $g, $b] = $this->rgb();

        return ($r * 299 + $g * 587 + $b * 114) / 1000;
    }

    /** WCAG contrast ratio. */
    public function contrast(self $other): float
    {
        $a = $this->luminance();
        $b = $other->luminance();

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }

    private function luminance(): float
    {
        $c = array_map(function (int $v) {
            $v /= 255;

            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, $this->rgb());

        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    }

    private static function clamp(float $v): float
    {
        return max(0.0, min(100.0, $v));
    }
}
