<?php

namespace Ernestdefoe\Festoon\Style;

/**
 * The drawings: garlands, ornaments, corner pieces, hats and the snow trim.
 *
 * Each is a small SVG used as a CSS background image, so nothing is added to
 * the page's markup and Flarum's own components are never touched. The motion
 * lives inside each SVG as CSS keyframes — browsers run an SVG image's own
 * animations — and every animated piece has a still twin (the same drawing
 * with its <style> left out) for people who ask their device for less motion.
 *
 * Everything animates transform or opacity only, which the browser composites
 * without repainting the page.
 */
final class Art
{
    /** Keyframes by class name; a drawing carries only the ones it uses. */
    private const MOTION = [
        'tw' => '.tw{animation:tw 1.6s ease-in-out infinite}@keyframes tw{0%,100%{opacity:1}50%{opacity:.25}}',
        'gl' => '.gl{animation:gl 1.6s ease-in-out infinite}@keyframes gl{0%,100%{opacity:.5}50%{opacity:0}}',
        'sw' => '.sw{transform-box:fill-box;transform-origin:50% 0;animation:sw 3.2s ease-in-out infinite}@keyframes sw{0%,100%{transform:rotate(-6deg)}50%{transform:rotate(6deg)}}',
        'fl' => '.fl{transform-box:fill-box;transform-origin:50% 0;animation:fl 1.8s ease-in-out infinite}@keyframes fl{0%,100%{transform:rotate(-4deg)}50%{transform:rotate(4deg) scaleY(.92)}}',
        'fk' => '.fk{transform-box:fill-box;transform-origin:50% 100%;animation:fk .9s ease-in-out infinite}@keyframes fk{0%,100%{transform:none}30%{transform:scale(.9,1.12);opacity:.85}60%{transform:scale(1.05,.92)}}',
        'bt' => '.bt{transform-box:fill-box;transform-origin:50% 50%;animation:bt 1.4s ease-in-out infinite}@keyframes bt{0%,60%,100%{transform:none}15%{transform:scale(1.18)}45%{transform:scale(1.1)}}',
        'sp' => '.sp{transform-box:fill-box;transform-origin:50% 50%;animation:sp 2.2s ease-in-out infinite}@keyframes sp{0%,100%{transform:none}50%{transform:scale(.55) rotate(45deg);opacity:.5}}',
        'rt' => '.rt{transform-box:fill-box;transform-origin:50% 50%;animation:rt 9s linear infinite}@keyframes rt{to{transform:rotate(360deg)}}',
        'gr' => '.gr{transform-box:fill-box;transform-origin:50% 100%;animation:gr 3s ease-in-out infinite}@keyframes gr{0%,100%{transform:rotate(-5deg)}50%{transform:rotate(5deg)}}',
        'bb' => '.bb{transform-box:fill-box;transform-origin:50% 0;animation:bb 4s ease-in-out infinite}@keyframes bb{0%,100%{transform:rotate(-3deg)}50%{transform:rotate(3deg)}}',
    ];

    public const GARLAND_HEIGHT = 44;

    /** Whether any part of the drawing moves. */
    public static function moves(string $body): bool
    {
        return (bool) preg_match("/class='(\\w+)'/", $body);
    }

    /** A data: URI for an SVG body of the given size, animated or still. */
    public static function uri(string $body, int $w, int $h, bool $animated): string
    {
        $style = '';
        if ($animated && preg_match_all("/class='(\\w+)'/", $body, $m)) {
            $style = '<style>'.implode('', array_intersect_key(self::MOTION, array_flip($m[1]))).'</style>';
        }
        $svg = "<svg xmlns='http://www.w3.org/2000/svg' width='$w' height='$h' viewBox='0 0 $w $h'>".$style.$body.'</svg>';

        return 'data:image/svg+xml,'.strtr(str_replace('"', "'", $svg), ['%' => '%25', '#' => '%23', '<' => '%3C', '>' => '%3E', "\n" => '']);
    }

    /** A negative delay from an item's index, so neighbours never move together. */
    private static function d(int $i, float $step, float $span): string
    {
        return sprintf("style='animation-delay:-%.2fs'", fmod($i * $step, $span));
    }

    /** Point on the swag between two hooks, t in 0–1. */
    private static function swag(string $wire, callable $item, int $span = 120, array $ts = [.25, .5, .75], int $sag = 22): string
    {
        $s = '';
        for ($x = 0; $x < 960; $x += $span) {
            $s .= sprintf("<path d='M%d 3 Q%d %d %d 3' fill='none' stroke='%s' stroke-width='1.6'/>", $x, $x + $span / 2, $sag, $x + $span, $wire);
        }
        $i = 0;
        for ($x = 0; $x < 960; $x += $span) {
            foreach ($ts as $t) {
                $px = $x + $span * $t;
                $py = (1 - $t) ** 2 * 3 + 2 * (1 - $t) * $t * $sag + $t * $t * 3;
                $s .= $item(round($px, 1), round($py, 1), $i++);
            }
        }

        return $s;
    }

    /** The garland hung along the header's bottom edge: a 960 × 44 tile. */
    public static function garland(string $kind): ?string
    {
        $d = [self::class, 'd'];

        return match ($kind) {
            'lights' => self::swag('#2f4f2f', function ($x, $y, $i) use ($d) {
                $c = ['#d32f2f', '#2e7d32', '#f2b705', '#1565c0'][$i % 4];
                $a = $d($i, .37, 1.6);

                return "<rect x='".($x - 1.5)."' y='$y' width='3' height='3' fill='#555'/><circle class='gl' $a cx='$x' cy='".($y + 8)."' r='10' fill='$c' opacity='.5'/><ellipse class='tw' $a cx='$x' cy='".($y + 8)."' rx='3.6' ry='5.4' fill='$c'/>";
            }, 120, [.25, .5, .75], 18),
            'hearts' => self::swag('#c98ba0', fn ($x, $y, $i) => "<g transform='translate($x ".($y + 7).")'>".self::heart($i % 2 ? '#c2185b' : '#f48fb1', 'bt', $d($i, .29, 1.4)).'</g>'),
            'shamrocks' => self::swag('#2e7d32', fn ($x, $y, $i) => "<g transform='translate($x ".($y + 6).") scale(.55)'><g class='sw' {$d($i, .5, 3.2)}>".self::shamrock($i % 2 ? '#2e7d32' : '#43a047').'</g></g>'),
            'eggs' => self::swag('#b39ddb', fn ($x, $y, $i) => "<g class='sw' {$d($i, .6, 3.2)}><line x1='$x' y1='$y' x2='$x' y2='".($y + 2)."' stroke='#b39ddb'/><ellipse cx='$x' cy='".($y + 9)."' rx='5.5' ry='7.5' fill='".['#f8bbd0', '#b3e5fc', '#fff59d', '#c5e1a5', '#d1c4e9'][$i % 5]."' stroke='#9e9e9e' stroke-width='.6'/></g>"),
            'bunting-usa' => self::swag('#3c3b6e', fn ($x, $y, $i) => "<path class='fl' {$d($i, .23, 1.8)} d='M".($x - 9)." $y L".($x + 9)." $y L$x ".($y + 16)." Z' fill='".['#b22234', '#ffffff', '#3c3b6e'][$i % 3]."' stroke='#3c3b6e' stroke-width='.6'/>", 120, [.2, .4, .6, .8]),
            'bunting-halloween' => self::swag('#333333', fn ($x, $y, $i) => "<path class='fl' {$d($i, .23, 1.8)} d='M".($x - 8)." $y L".($x + 8)." $y L$x ".($y + 15)." Z' fill='".($i % 2 ? '#f07f13' : '#5b2a86')."'/>", 120, [.2, .4, .6, .8]),
            'leaves' => self::swag('#7a4a1e', fn ($x, $y, $i) => "<g transform='translate($x $y)'>".self::leaf(['#c2551b', '#d98a1e', '#9e3d12'][$i % 3], 'sw', $d($i, .41, 3.2)).'</g>'),
            'snowflakes' => self::swag('#90a4ae', fn ($x, $y, $i) => "<g transform='translate($x ".($y + 8).")'>".self::flake($i % 2 ? '#5c8db8' : '#90b4d4', $d($i, 1.3, 9)).'</g>', 160, [.5], 14),
            'lanterns' => self::swag('#8b1a1a', fn ($x, $y, $i) => "<g class='sw' {$d($i, .8, 3.2)}>".self::lantern($x, $y).'</g>', 160, [.5], 14),
            'diyas' => self::swag('#8d5524', fn ($x, $y, $i) => self::diya($x, $y, $d($i, .17, .9)), 120, [.25, .5, .75], 16),
            'beads' => self::swag('#90a4ae', fn ($x, $y, $i) => "<circle class='tw' style='animation-duration:2.4s;animation-delay:-".sprintf('%.2f', fmod($i * .53, 2.4))."s' cx='$x' cy='".($y + 5)."' r='3.4' fill='".($i % 2 ? '#1565c0' : '#cfd8dc')."' stroke='#78909c' stroke-width='.5'/>", 120, [.17, .33, .5, .67, .83]),
            'tulips' => self::swag('#558b2f', fn ($x, $y, $i) => "<g transform='translate($x ".($y + 1).")'><g class='sw' {$d($i, .55, 3.2)}>".($i % 3 === 1 ? self::hyacinth($i % 2 ? '#7e57c2' : '#9575cd') : self::tulip(['#e53935', '#f06292', '#e53935', '#ffb300'][$i % 4])).'</g></g>'),
            'fanous' => self::swag('#a0884a', fn ($x, $y, $i) => "<g class='sw' {$d($i, .8, 3.2)}><line x1='$x' y1='$y' x2='$x' y2='".($y + 3)."' stroke='#a0884a'/><path d='M".($x - 4).' '.($y + 3).' L'.($x + 4).' '.($y + 3).' L'.($x + 6).' '.($y + 10).' L'.($x + 3).' '.($y + 18).' L'.($x - 3).' '.($y + 18).' L'.($x - 6).' '.($y + 10)." Z' fill='".($i % 2 ? '#c9a227' : '#00796b')."'/><rect class='tw' style='animation-duration:2s' x='".($x - 2)."' y='".($y + 7)."' width='4' height='7' fill='#ffe082'/></g>", 160, [.5], 14),
            default => null,
        };
    }

    /** Beside the forum title: 48 × 36. */
    public static function ornament(string $kind): ?string
    {
        return match ($kind) {
            'sparkles' => implode('', array_map(fn ($p, $i) => "<g transform='translate($p[0] $p[1]) scale($p[2])'>".self::star('#c9a227', 'sp', self::d($i, .7, 2.2)).'</g>', [[14, 10, 1], [28, 22, .6], [8, 26, .6]], [0, 1, 2])),
            'bat' => "<g class='bb' style='transform-origin:50% 50%;animation-duration:2.4s'><path transform='translate(10 12)' d='M0 8 Q4 0 8 6 Q10 2 12 6 Q14 2 16 6 Q20 0 24 8 Q18 6 16 10 Q12 8 8 10 Q6 6 0 8Z' fill='#2b2b2b'/></g>",
            'blossom' => "<path d='M2 30 Q24 18 46 22' stroke='#6d4c41' stroke-width='2' fill='none'/>".implode('', array_map(fn ($p) => "<g transform='translate($p[0] $p[1])'><g class='bt' style='animation-duration:3s'>".self::blossom().'</g></g>', [[14, 22], [28, 17], [40, 20]])),
            'sun' => "<g transform='translate(20 18)'><circle r='8' fill='#ffb300'/><g class='rt' style='animation-duration:24s'>".implode('', array_map(fn ($i) => sprintf("<line x1='%.1f' y1='%.1f' x2='%.1f' y2='%.1f' stroke='#ffb300' stroke-width='2' stroke-linecap='round'/>", cos($i * M_PI / 5) * 11, sin($i * M_PI / 5) * 11, cos($i * M_PI / 5) * 15, sin($i * M_PI / 5) * 15), range(0, 9))).'</g></g>',
            'leaves' => implode('', array_map(fn ($p, $i) => "<g transform='translate($p[0] $p[1]) rotate($p[2])'>".self::leaf($p[3], 'sw', self::d($i, .9, 3.2)).'</g>', [[10, 6, -20, '#c2551b'], [24, 12, 30, '#d98a1e'], [36, 4, 5, '#9e3d12']], [0, 1, 2])),
            'menorah' => "<g transform='translate(2 8)' fill='#1565c0'>".implode('', array_map(fn ($i) => "<rect x='".($i * 5)."' y='".($i === 4 ? 0 : 4)."' width='2' height='".($i === 4 ? 20 : 16)."'/><path class='fk' ".self::d($i, .13, .9)." d='M".($i * 5 + 1).' '.($i === 4 ? -5 : -1)." q2 2 0 4 q-2 -2 0 -4z' fill='#f2b705'/>", range(0, 8)))."<rect x='-1' y='20' width='44' height='2.5'/><rect x='19' y='20' width='4' height='7'/><rect x='13' y='26' width='16' height='2.5'/></g>",
            'crescent' => "<g transform='translate(18 18)'>".self::crescent().'</g>',
            'sabzeh' => "<g transform='translate(24 0)'>".self::sabzeh().'</g>',
            default => ($icon = self::icon($kind)) ? "<g transform='translate(7 1)'>$icon</g>" : null,
        };
    }

    /** Corner pieces for the welcome banner, widgets and the first post: 34 × 34. */
    public static function corner(string $kind): ?string
    {
        return match ($kind) {
            'holly' => "<g transform='translate(17 17)'><path d='M-2 0 C-8 -10 -16 -6 -15 -1 C-12 -3 -10 2 -6 0 C-8 4 -4 6 -2 0Z' fill='#2e7d32'/><path d='M2 0 C8 -10 16 -6 15 -1 C12 -3 10 2 6 0 C8 4 4 6 2 0Z' fill='#1b5e20'/><circle class='tw' style='animation-duration:3s' cx='-3' cy='3' r='3.4' fill='#d32f2f'/><circle cx='3' cy='3' r='3.4' fill='#c62828'/><circle cx='0' cy='-2' r='3.4' fill='#e53935'/></g>",
            'web' => "<g stroke='#9e9e9e' stroke-width='.8' fill='none'><path d='M34 0 L0 0 M34 0 L4 18 M34 0 L16 30 M34 0 L34 34'/><path d='M24 0 Q26 6 34 8 M14 0 Q20 12 34 16 M6 0 Q14 18 34 26'/></g><g class='bb'><line x1='22' y1='0' x2='22' y2='20' stroke='#666' stroke-width='.8'/><circle cx='22' cy='23' r='3.5' fill='#2b2b2b'/></g>",
            'stars' => self::stars([[10, 10, 1, '#b22234'], [24, 16, .7, '#3c3b6e'], [14, 25, .55, '#b22234']]),
            'sparkles' => self::stars([[12, 12, 1, '#c9a227'], [25, 20, .6, '#c9a227'], [14, 27, .5, '#c9a227']]),
            'hearts' => "<g transform='translate(12 14)'>".self::heart('#c2185b', 'bt', '')."</g><g transform='translate(24 22) scale(.7)'>".self::heart('#f48fb1', 'bt', self::d(1, .5, 1.4)).'</g>',
            'leaves' => implode('', array_map(fn ($p, $i) => "<g transform='translate($p[0] $p[1]) rotate($p[2])'>".self::leaf($p[3], 'sw', self::d($i, .9, 3.2)).'</g>', [[10, 4, -30, '#c2551b'], [20, 6, 15, '#d98a1e'], [15, 12, 60, '#9e3d12']], [0, 1, 2])),
            'flake' => "<g transform='translate(17 17) scale(1.8)'>".self::flake('#5c8db8', '').'</g>',
            'lantern' => "<g class='sw'>".self::lantern(17, 0).'</g>',
            'diya' => "<g transform='translate(-5 6) scale(1.3)'>".self::diya(17, 8, '').'</g>',
            'dreidel' => "<g class='bb'><rect x='15.5' y='3' width='3' height='6' fill='#90a4ae'/><path d='M9 9 H25 V22 L17 31 L9 22 Z' fill='#1565c0'/><path d='M14 13 h6 v2 h-4 v4 h-2z' fill='#cfd8dc'/></g>",
            'crescent' => "<g transform='translate(16 18) scale(1.2)'>".self::crescent().'</g>',
            'shamrock' => "<g transform='translate(17 15)'><g class='sw'>".self::shamrock('#2e7d32').'</g></g>',
            'egg' => "<g class='bb' style='transform-origin:50% 100%'><ellipse cx='17' cy='18' rx='9' ry='12' fill='#b3e5fc' stroke='#7e57c2' stroke-width='.8'/><path d='M8.5 16 Q13 12 17 16 T25.5 16' stroke='#f06292' stroke-width='2' fill='none'/><path d='M9 22 h16' stroke='#9ccc65' stroke-width='2'/></g>",
            'sun' => "<g transform='translate(17 17)'><circle r='7' fill='#ffb300'/><g class='rt' style='animation-duration:24s'>".implode('', array_map(fn ($i) => sprintf("<line x1='%.1f' y1='%.1f' x2='%.1f' y2='%.1f' stroke='#ffb300' stroke-width='2' stroke-linecap='round'/>", cos($i * M_PI / 4) * 10, sin($i * M_PI / 4) * 10, cos($i * M_PI / 4) * 14, sin($i * M_PI / 4) * 14), range(0, 7))).'</g></g>',
            'blossom' => "<path d='M2 30 Q16 20 32 22' stroke='#6d4c41' stroke-width='2' fill='none'/><g transform='translate(12 21)'><g class='bt' style='animation-duration:3s'>".self::blossom()."</g></g><g transform='translate(25 18)'>".self::blossom().'</g>',
            'hyacinth' => "<g transform='translate(10 2)'><g class='gr'><path d='M2 22 V8' stroke='#558b2f' stroke-width='1.6'/>".self::hyacinth('#7e57c2')."</g><path d='M-4 22 h12 l-2 9 h-8 z' fill='#00838f'/></g><g transform='translate(25 24)'><g class='bb' style='transform-origin:50% 100%'><ellipse rx='5.5' ry='7' fill='#ffcdd2' stroke='#e53935' stroke-width='.7'/><path d='M-5 -1 q2.5 -3 5 0 t5 0' stroke='#00838f' stroke-width='1.4' fill='none'/></g></g>",
            default => self::icon($kind),
        };
    }

    /**
     * The international days' pieces: one 34 × 34 drawing each, used both
     * beside the forum's name and as the ornament. Each has a light outline
     * so it still reads on a dark header or a dark-mode page.
     */
    private static function icon(string $kind): ?string
    {
        return match ($kind) {
            'pi' => "<circle cx='17' cy='17' r='15' fill='#fff3e0' stroke='#ef6c00' stroke-width='1.5'/><g class='bb' style='transform-origin:50% 50%' stroke='#4a148c' stroke-width='3' fill='none' stroke-linecap='round'><path d='M8 12 Q9.5 9.5 12.5 10 H26'/><path d='M14 10 V24'/><path d='M21.5 10 Q21 21 25.5 24'/></g>",
            'palette' => "<path d='M17 3 C26 3 32 9 32 16 C32 21 28 22 25 21 C22 20 20 22 21 25 C22 29 19 31 16 31 C8 31 2 25 2 17 C2 9 9 3 17 3 Z' fill='#f5deb3' stroke='#8d6e63' stroke-width='1.2'/><circle cx='11' cy='23' r='2.6' fill='#d7b98e'/>"
                .implode('', array_map(fn ($p, $i) => "<circle class='bt' ".self::d($i, .35, 1.4)." cx='$p[0]' cy='$p[1]' r='3' fill='$p[2]'/>", [[8, 15, '#e53935'], [12, 9, '#fdd835'], [19, 8, '#1e88e5'], [25, 12, '#43a047']], range(0, 3))),
            'camera' => "<rect x='9' y='6' width='9' height='5' rx='1.5' fill='#37474f' stroke='#cfd8dc'/><rect x='3' y='10' width='28' height='18' rx='3' fill='#37474f' stroke='#cfd8dc'/><circle cx='17' cy='19' r='6.5' fill='#90caf9' stroke='#eceff1' stroke-width='2'/><circle cx='17' cy='19' r='2.5' fill='#1565c0'/><rect class='tw' x='24' y='13' width='4' height='3' rx='.8' fill='#ffeb3b'/>",
            'chess' => "<g class='bb' style='transform-origin:50% 100%'><path d='M9 30 H26 V27 H24 C24 21 26.5 17 25 12 C23.5 7 20 4.5 15.5 5 L13.5 2.5 L12 6 C9 8 7 11.5 7 15 L9.5 17.5 L13.5 15.5 C14.5 17.5 12.5 20 11.5 22 C10.5 24 10.5 26 11.5 27 H9 Z' fill='#4e342e' stroke='#d7ccc8' stroke-width='1.2' stroke-linejoin='round'/><circle cx='15' cy='10' r='1.3' fill='#d7ccc8'/></g>",
            'code' => "<rect x='2' y='5' width='30' height='24' rx='4' fill='#263238' stroke='#90a4ae'/><path d='M11 11 L6 17 L11 23 M19 9.5 L15 24.5 M23 11 L28 17 L23 23' stroke='#69f0ae' stroke-width='2.4' fill='none' stroke-linecap='round' stroke-linejoin='round'/>",
            'unlock' => "<path d='M11 15 V10 C11 3.5 21 3.5 21 10 V11' stroke='#90a4ae' stroke-width='3' fill='none' stroke-linecap='round'/><g class='bb' style='transform-origin:50% 0'><rect x='7' y='15' width='20' height='16' rx='2.5' fill='#f9a825' stroke='#795548'/><circle cx='17' cy='21.5' r='2.3' fill='#5d4037'/><path d='M17 22 V26.5' stroke='#5d4037' stroke-width='2' stroke-linecap='round'/></g>",
            'apple' => "<rect x='4' y='24' width='26' height='6' rx='1' fill='#1565c0' stroke='#e3f2fd' stroke-width='.8'/><rect x='6' y='18.5' width='23' height='5.5' rx='1' fill='#2e7d32' stroke='#e8f5e9' stroke-width='.8'/><g class='bb' style='transform-origin:50% 100%'><path d='M17 8.5 C13 5.5 8 8 9 12.5 C10 16.5 14 18 17 17 C20 18 24 16.5 25 12.5 C26 8 21 5.5 17 8.5 Z' fill='#e53935' stroke='#ffcdd2' stroke-width='.8'/><path d='M17 8.5 L18 3.5' stroke='#5d4037' stroke-width='1.6' stroke-linecap='round'/><ellipse cx='21' cy='4.5' rx='3' ry='1.5' transform='rotate(-25 21 4.5)' fill='#43a047'/></g>",
            'atom' => "<g transform='translate(17 17)'><g class='rt' style='animation-duration:18s' fill='none' stroke='#1e88e5' stroke-width='1.6'>"
                .implode('', array_map(fn ($a) => "<g transform='rotate($a)'><ellipse rx='14' ry='5'/><circle cx='14' r='1.8' fill='#90caf9' stroke='none'/></g>", [0, 60, 120]))
                ."</g><circle r='3.4' fill='#e53935' stroke='#ffcdd2'/></g>",
            'cap' => "<path d='M9 15 V22 C9 26 25 26 25 22 V15 L17 18.5 Z' fill='#37474f' stroke='#b0bec5'/><path d='M17 6 L32 12 L17 18 L2 12 Z' fill='#263238' stroke='#b0bec5' stroke-linejoin='round'/><g class='sw' style='transform-origin:0 0'><path d='M17 12 L27 14 V22' stroke='#ffb300' stroke-width='1.4' fill='none'/><rect x='25.5' y='21' width='3' height='5' rx='1' fill='#ffb300'/></g>",
            default => null,
        };
    }

    /** Hats, drawn to sit on a 64px avatar: 60 × 36, brim along the bottom. */
    public static function hat(string $kind): ?string
    {
        return match ($kind) {
            'santa' => "<path d='M8 30 C10 12 22 2 38 8 C44 10 46 18 44 24 C40 16 34 14 30 18 L52 30 Z' fill='#d32f2f'/><rect x='4' y='27' width='52' height='8' rx='4' fill='#fff' stroke='#ddd'/><circle cx='45' cy='25' r='5' fill='#fff' stroke='#ddd'/>",
            'witch' => "<path d='M18 30 L32 0 L44 30 Z' fill='#2b2b2b'/><rect x='2' y='28' width='56' height='6' rx='3' fill='#2b2b2b'/><rect x='18' y='24' width='26' height='4' fill='#f07f13'/>",
            'tophat' => "<rect x='16' y='4' width='28' height='26' fill='#3c3b6e'/><rect x='16' y='18' width='28' height='5' fill='#b22234'/><rect x='16' y='23' width='28' height='3' fill='#fff'/><rect x='6' y='28' width='48' height='5' rx='2' fill='#3c3b6e'/><g transform='translate(30 11) scale(.5)'>".self::star('#ffffff', '', '').'</g>',
            'party' => "<path d='M16 34 L30 2 L44 34 Z' fill='#1f3a5f'/><path d='M22 20 L38 20 M19 28 L41 28' stroke='#c9a227' stroke-width='3'/><circle class='tw' cx='30' cy='3' r='4' fill='#c9a227'/>",
            'beanie' => "<path d='M8 32 C8 10 52 10 52 32 Z' fill='#37628a'/><rect x='6' y='26' width='48' height='9' rx='3' fill='#90a4ae'/><circle cx='30' cy='9' r='6' fill='#cfd8dc'/>",
            'leprechaun' => "<rect x='17' y='6' width='26' height='24' fill='#2e7d32'/><rect x='17' y='20' width='26' height='5' fill='#2b2b2b'/><rect x='26' y='19' width='8' height='7' fill='none' stroke='#c9a227' stroke-width='2'/><rect x='6' y='28' width='48' height='6' rx='3' fill='#2e7d32'/>",
            'bunny' => "<g class='bb' style='transform-origin:50% 100%'><ellipse cx='20' cy='14' rx='6' ry='15' fill='#f5f5f5' stroke='#ddd'/><ellipse cx='20' cy='14' rx='3' ry='11' fill='#f8bbd0'/><ellipse cx='40' cy='14' rx='6' ry='15' fill='#f5f5f5' stroke='#ddd'/><ellipse cx='40' cy='14' rx='3' ry='11' fill='#f8bbd0'/></g><path d='M10 34 Q30 24 50 34' stroke='#f5f5f5' stroke-width='5' fill='none'/>",
            default => null,
        };
    }

    /** The scalloped snow that sits along the top of a button or widget: a 28 × 12 tile. */
    public static function snow(): string
    {
        return "<path d='M0 6 Q7 0 14 4 Q21 0 28 6 V8 Q24 12 20 9 Q14 13 9 9 Q4 11 0 8 Z' fill='#fff' stroke='#c9d6e3' stroke-width='.8'/>";
    }

    private static function star(string $fill, string $class, string $style): string
    {
        return "<path class='$class' $style d='M0 -8 L2 -2 L8 0 L2 2 L0 8 L-2 2 L-8 0 L-2 -2 Z' fill='$fill'/>";
    }

    /** @param list<array{0: int|float, 1: int|float, 2: float|int, 3: string}> $points */
    private static function stars(array $points): string
    {
        $s = '';
        foreach ($points as $i => [$x, $y, $scale, $c]) {
            $s .= "<g transform='translate($x $y) scale($scale)'>".self::star($c, 'sp', self::d($i, .7, 2.2)).'</g>';
        }

        return $s;
    }

    private static function heart(string $fill, string $class, string $style): string
    {
        return "<path class='$class' $style d='M0 6 C-10 -2 -7 -10 0 -5 C7 -10 10 -2 0 6 Z' fill='$fill'/>";
    }

    private static function leaf(string $fill, string $class, string $style): string
    {
        return "<path class='$class' $style d='M0 0 C5 3 6 10 0 16 C-6 10 -5 3 0 0 Z' fill='$fill'/>";
    }

    /** Six arms, each with a pair of branches, so it reads as a snowflake and not a star. */
    private static function flake(string $color, string $style): string
    {
        $s = "<g class='rt' $style stroke='$color' stroke-width='1.4' stroke-linecap='round' fill='none'>";
        foreach ([0, 60, 120, 180, 240, 300] as $a) {
            $s .= "<g transform='rotate($a)'><path d='M0 0 V-7 M0 -4 L-2.4 -6 M0 -4 L2.4 -6'/></g>";
        }

        return $s.'</g>';
    }

    private static function shamrock(string $fill): string
    {
        return "<g fill='$fill'><circle cx='0' cy='-6' r='6'/><circle cx='-6' cy='2' r='6'/><circle cx='6' cy='2' r='6'/><rect x='-1' y='4' width='2' height='9'/></g>";
    }

    private static function lantern(float $x, float $y): string
    {
        return "<line x1='$x' y1='$y' x2='$x' y2='".($y + 4)."' stroke='#c9a227' stroke-width='1.2'/><ellipse cx='$x' cy='".($y + 12)."' rx='8' ry='8' fill='#d32f2f'/><rect x='".($x - 4)."' y='".($y + 3)."' width='8' height='2' fill='#c9a227'/><rect x='".($x - 4)."' y='".($y + 19)."' width='8' height='2' fill='#c9a227'/><line x1='$x' y1='".($y + 21)."' x2='$x' y2='".($y + 28)."' stroke='#c9a227' stroke-width='1.4'/>";
    }

    private static function diya(float $x, float $y, string $style): string
    {
        return "<path d='M".($x - 7).' '.($y + 8).' Q'.$x.' '.($y + 16).' '.($x + 7).' '.($y + 8)." Z' fill='#b5651d'/><path class='fk' $style d='M$x ".($y - 1).' Q'.($x + 3).' '.($y + 4).' '.$x.' '.($y + 8).' Q'.($x - 3).' '.($y + 4).' '.$x.' '.($y - 1)." Z' fill='#ffb300'/>";
    }

    private static function crescent(): string
    {
        return "<path d='M3 -10.5 A11 11 0 1 0 9.5 5 A9.5 9.5 0 1 1 3 -10.5 Z' fill='#c9a227'/><g transform='translate(12 -6) scale(.5)'>".self::star('#c9a227', 'sp', '').'</g>';
    }

    /** A tulip head on a short stem, hanging from the garland wire. */
    private static function tulip(string $fill): string
    {
        return "<path d='M0 0 V5' stroke='#558b2f' stroke-width='1.2'/><path d='M-5 6 Q-5 15 0 15 Q5 15 5 6 L2.5 9 L0 5 L-2.5 9 Z' fill='$fill'/>";
    }

    /** A hyacinth (sonbol) spike: a column of small florets. */
    private static function hyacinth(string $fill): string
    {
        $s = "<g fill='$fill'>";
        foreach ([[0, 2], [-2.4, 4.5], [2.4, 4.5], [0, 6.5], [-2.4, 9], [2.4, 9], [0, 11], [-1.6, 13.5], [1.6, 13.5]] as [$x, $y]) {
            $s .= "<circle cx='$x' cy='$y' r='1.9'/>";
        }

        return $s.'</g>';
    }

    /** Sabzeh: sprouted wheatgrass in a turquoise dish, tied with a red ribbon. 24 wide, centred on 0. */
    private static function sabzeh(): string
    {
        $blades = '';
        foreach (range(-9, 9, 2) as $i => $x) {
            $h = 12 + ($i * 7) % 6;
            $blades .= sprintf("<path class='gr' %s d='M%d 24 Q%.1f %d %.1f %d' stroke='%s' stroke-width='1.6' fill='none' stroke-linecap='round'/>", self::d($i, .37, 3), $x, $x + 1.5, 24 - $h / 2, $x + ($i % 2 ? 2 : -2), 24 - $h, $i % 3 ? '#7cb342' : '#558b2f');
        }

        return $blades."<path d='M-12 23 h24 q-1 9 -12 9 q-11 0 -12 -9 z' fill='#00838f'/><path d='M-11.5 26 h23' stroke='#e53935' stroke-width='2'/>";
    }

    private static function blossom(): string
    {
        $s = "<g fill='#f48fb1'>";
        foreach ([0, 72, 144, 216, 288] as $a) {
            $s .= sprintf("<circle cx='%.1f' cy='%.1f' r='3'/>", cos(deg2rad($a)) * 3.5, sin(deg2rad($a)) * 3.5);
        }

        return $s."<circle r='1.6' fill='#ffeb3b'/></g>";
    }
}
