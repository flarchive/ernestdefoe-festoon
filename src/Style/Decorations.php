<?php

namespace Ernestdefoe\Festoon\Style;

use Ernestdefoe\Festoon\Look\Look;

/**
 * The decoration stylesheet for one look.
 *
 * It is written once to a file under assets/ and served by the web server
 * with the forum's other assets, so a member downloads it once per look, not
 * with every page. Every rule hangs off html:not(.Festoon-off), which is how a
 * member's "turn off decorations" switch takes effect the moment it is
 * clicked, before the next page load leaves the file out altogether.
 *
 * Decorations are pseudo-elements on Flarum's own elements, so no component
 * is replaced and nothing else's markup changes.
 */
final class Decorations
{
    private const ON = 'html:not(.Festoon-off) ';

    /** @param array<string, bool> $parts */
    public static function css(Look $look, array $parts): string
    {
        $css = '';
        $still = '';
        $on = self::ON;

        // The image itself goes in one rule per selector list, so a drawing used
        // in three places is in the file once; a still twin only when it moves.
        // $under is an extra image layer drawn beneath the drawing.
        $bg = function (string $selector, string $body, int $w, int $h, string $rest, string $under = '') use (&$css, &$still) {
            $css .= "$selector{background:url(\"".Art::uri($body, $w, $h, true)."\") $rest".($under ? ",$under" : '').'}';
            if (Art::moves($body)) {
                $still .= "$selector{background-image:url(\"".Art::uri($body, $w, $h, false).'")'.($under ? ','.strtok($under, ' ') : '').'}';
            }
        };

        if ($parts['header'] ?? true) {
            if ($garland = Art::garland($look->get('garland'))) {
                // Desktop: hung from the header's bottom edge. On phones the
                // header is the slide-out drawer, so it hangs from the top bar.
                $css .= $on.'.App-header::after,'.$on.'.App-navigation::after{content:"";position:absolute;left:0;right:0;top:100%;height:'.Art::GARLAND_HEIGHT.'px;pointer-events:none;z-index:2}'
                    .'@media (max-width:767.98px){'.$on.'.App-header::after{display:none}}'
                    .'@media (min-width:768px){'.$on.'.App-navigation::after{display:none}}';
                $bg($on.'.App-header::after,'.$on.'.App-navigation::after', $garland, 960, Art::GARLAND_HEIGHT, '0 0/960px '.Art::GARLAND_HEIGHT.'px repeat-x');
            }

            if ($ornament = Art::ornament($look->get('ornament'))) {
                $css .= $on.'.Header-title::after{content:"";display:inline-block;width:48px;height:36px;margin-left:6px;vertical-align:middle;pointer-events:none}';
                $bg($on.'.Header-title::after', $ornament, 48, 36, 'center/contain no-repeat');
            }
        }

        if (($parts['ornaments'] ?? true) && ($corner = Art::corner($look->get('corner')))) {
            // The welcome banner, in both bottom corners, the right one mirrored.
            $css .= $on.'.WelcomeHero{position:relative}'
                .$on.'.WelcomeHero::before,'.$on.'.WelcomeHero::after{content:"";position:absolute;bottom:10px;width:64px;height:64px;pointer-events:none}'
                .$on.'.WelcomeHero::before{left:16px}'.$on.'.WelcomeHero::after{right:16px;transform:scaleX(-1)}'
                .'@media (max-width:767.98px){'.$on.'.WelcomeHero::before,'.$on.'.WelcomeHero::after{display:none}}';

            // Widgets — Bespoke's, and every widget FoF Widgets places — and the
            // opening post get the same piece, tucked into the top-right corner.
            $widget = $on.'.Bespoke-widget::after,'.$on.'.FofWidgets-WidgetSection>*::after,'.$on.'.PostStream-item[data-number="1"] .CommentPost::after';
            $css .= $on.'.FofWidgets-WidgetSection>*,'.$on.'.PostStream-item[data-number="1"] .CommentPost{position:relative}'
                .$widget.'{content:"";position:absolute;top:-10px;right:-6px;width:34px;height:34px;pointer-events:none;z-index:1}';

            // A small one in the middle of each divider between posts.
            $divider = $on.'.PostStream-item:has(+.PostStream-item)::after';
            $css .= $divider.'{height:18px;margin-top:-9px;margin-bottom:-9px;background-color:transparent;position:relative;z-index:1}';
            $bg($on.'.WelcomeHero::before,'.$on.'.WelcomeHero::after,'.$widget, $corner, 34, 34, 'center/contain no-repeat');
            // The divider keeps core's line, drawn under the ornament.
            $bg($divider, $corner, 34, 34, 'center/18px 18px no-repeat', 'linear-gradient(var(--control-bg),var(--control-bg)) 0 50%/100% 1px no-repeat');
        }

        if ($parts['trims'] ?? true) {
            $trim = $look->trim();
            $targets = [$on.'.IndexPage-newDiscussion', $on.'.DiscussionPage-nav .SplitDropdown-button.Button--primary', $on.'.Bespoke-widget', $on.'.FofWidgets-WidgetSection>*'];
            $before = implode(',', array_map(fn ($t) => "$t::before", $targets));

            if ($trim === 'snow') {
                $css .= implode(',', $targets).'{position:relative;overflow:visible}'
                    .$before.'{content:"";position:absolute;left:2px;right:2px;top:-6px;height:12px;pointer-events:none;z-index:1;background:url("'.Art::uri(Art::snow(), 28, 12, false).'") 0 0/28px 12px repeat-x}';
            } elseif (is_array($trim)) {
                $stops = [];
                foreach ($trim as $i => $c) {
                    $stops[] = $c.' '.($i * 8).'px '.(($i + 1) * 8).'px';
                }
                $css .= implode(',', $targets).'{position:relative;overflow:visible}'
                    .$before.'{content:"";position:absolute;left:3px;right:3px;top:0;height:3px;pointer-events:none;z-index:1;border-radius:2px;background:repeating-linear-gradient(90deg,'.implode(',', $stops).')}';
            }
        }

        if (($parts['hats'] ?? true) && ($hat = Art::hat($look->get('hat')))) {
            // On the big avatar beside each post (desktop; .Post-side holds it at
            // its top left) and on the small one in the post header (phones).
            $sel = $on.'.CommentPost .Post-side::before,'.$on.'.CommentPost .Post-header .PostUser-name a::before';
            $css .= $on.'.CommentPost .Post-side,'.$on.'.CommentPost .Post-header .PostUser-name a{position:relative}'
                .$sel.'{content:"";position:absolute;pointer-events:none;z-index:1}'
                .$on.'.CommentPost .Post-side::before{left:2px;top:-21px;width:60px;height:36px}'
                .$on.'.CommentPost .Post-header .PostUser-name a::before{left:1px;top:-17px;width:30px;height:18px}'
                .'@media (max-width:767.98px){'.$on.'.CommentPost .Post-side::before{display:none}}'
                .'@media (min-width:768px){'.$on.'.CommentPost .Post-header .PostUser-name a::before{display:none}}';
            $bg($sel, $hat, 60, 36, 'center/contain no-repeat');
        }

        if (($parts['effects'] ?? true) && $look->get('effect') === 'fireworks') {
            // A night sky on the welcome banner for the show to play on.
            $css .= $on.'.WelcomeHero{background:linear-gradient(#050a1f,#0e1a3d 70%,#1a2550);--hero-color:#c9d3ea;color:#c9d3ea}'
                .$on.'.WelcomeHero .Hero-title,'.$on.'.WelcomeHero h1{color:#fff}'
                .$on.'.WelcomeHero .container{position:relative;z-index:1}'
                .'@media (min-width:768px){'.$on.'.WelcomeHero .container{padding-top:64px;padding-bottom:56px}}'
                .'.Festoon-sky{position:absolute;inset:0;width:100%;height:100%;pointer-events:none}';
        }

        if ($parts['effects'] ?? true) {
            $css .= self::fallingCss();
        }

        if ($parts['effects'] ?? true) {
            $still .= '.Festoon-fall{display:none}';
        }

        return $still === '' ? $css : $css.'@media (prefers-reduced-motion:reduce){'.$still.'}';
    }

    /** Falling things: a few dozen fixed spans, moved by transform only. */
    private static function fallingCss(): string
    {
        return self::ON.'.Festoon-fall{position:fixed;inset:0;pointer-events:none;z-index:1100;overflow:hidden}'
            .'.Festoon-off .Festoon-fall{display:none}'
            .'.Festoon-fall i{position:absolute;top:-16px;left:var(--x);animation:festoon-fall var(--t) linear var(--d) infinite;opacity:.75}'
            .'@keyframes festoon-fall{to{transform:translate3d(var(--dx),calc(100vh + 32px),0) rotate(var(--r))}}'
            .'.Festoon-fall--snow i{width:var(--s);height:var(--s);border-radius:50%;background:#a9c3de}'
            .'.Festoon-fall--embers i{width:4px;height:4px;border-radius:50%;background:#f28c28;box-shadow:0 0 4px #ffb347}'
            .'.Festoon-fall--leaves i{width:9px;height:14px;border-radius:0 80% 0 80%;background:var(--c)}'
            .'.Festoon-fall--petals i{width:10px;height:6px;border-radius:50%;background:var(--c)}'
            .'.Festoon-fall--confetti i{width:8px;height:3px;background:var(--c)}'
            .'.Festoon-fall--hearts i{width:10px;height:10px;background:var(--c);clip-path:path("M5 9C-1 5 0 0 3 0c1 0 2 1 2 2c0-1 1-2 2-2c3 0 4 5-2 9z")}';
    }
}
