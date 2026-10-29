<?php

namespace Ernestdefoe\Festoon\Content;

use Ernestdefoe\Festoon\Config;
use Ernestdefoe\Festoon\Look\Look;
use Ernestdefoe\Festoon\Look\Schedule;
use Ernestdefoe\Festoon\Style\Decorations;
use Ernestdefoe\Festoon\Style\Palette;
use Flarum\Frontend\Document;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Puts today's look on the page, decided here on the server so the first paint
 * is already right — no flash of the forum's normal colours while a script
 * catches up.
 *
 * - The colours go inline in <head>, after the forum stylesheet, where core
 *   intends inline overrides to win. They are a few hundred bytes.
 * - The decorations are a stylesheet file per look, written once to assets/
 *   and cached by the browser like the forum's own CSS.
 * - Falling snow, leaves and the rest are a few dozen empty spans before
 *   </body>, moved by CSS. Only fireworks need a script, and it is added
 *   only on a day that has them.
 */
class InjectFestoon
{
    public const PREVIEW = 'festoon';

    protected Filesystem $assets;

    public function __construct(protected Config $config, protected Schedule $schedule, Factory $filesystems)
    {
        $this->assets = $filesystems->disk('flarum-assets');
    }

    public function __invoke(Document $document, Request $request): void
    {
        if (! $this->config->enabled()) {
            return;
        }

        $actor = RequestUtil::getActor($request);
        $preview = null;
        $asked = $request->getQueryParams()[self::PREVIEW] ?? null;

        if (is_string($asked) && $actor->isAdmin()) {
            $preview = $this->config->look($asked);
        }

        $look = $preview ?? $this->schedule->active();

        if ($look === null) {
            return;
        }

        $parts = $this->config->parts();

        if ($parts['colors']) {
            $document->head[] = '<style id="festoon-colors">'.(new Palette($look->get('primary'), $look->get('secondary')))->css().'</style>';
        }

        $off = ! $actor->isGuest() && $actor->getPreference('festoonDecorations') === false;

        if ($off) {
            $document->extraAttributes['class'][] = 'Festoon-off';
        } else {
            $document->head[] = '<link rel="stylesheet" href="'.e($this->stylesheet($look, $parts)).'">';

            if ($parts['effects']) {
                $document->foot[] = self::falling($look->get('effect'));

                if ($look->get('effect') === 'fireworks') {
                    $document->foot[] = '<script defer src="'.e($this->publish(__DIR__.'/../../js/dist/fireworks.js', 'fireworks', 'js')).'"></script>';
                }
            }
        }

        if ($preview !== null || $look->get('effect') === 'fireworks') {
            $document->payload['festoon'] = array_filter([
                'look' => $look->id,
                'preview' => $preview !== null,
                'name' => $preview !== null ? $look->name() : null,
                'sky' => $look->get('effect') === 'fireworks' && $parts['effects'] ? $look->sky() : null,
            ]);
        }
    }

    /**
     * The URL of this look's decoration stylesheet, writing it first if this
     * is the first request to need it. The name carries a hash of everything
     * that shapes it, so a changed setting or a new version of Festoon is a
     * new file and no browser keeps a stale one.
     *
     * @param array<string, bool> $parts
     */
    protected function stylesheet(Look $look, array $parts): string
    {
        $version = @filemtime(__DIR__.'/../Style/Decorations.php').'-'.@filemtime(__DIR__.'/../Style/Art.php');
        $hash = substr(md5(json_encode([$look->toArray(), $parts, $version]) ?: ''), 0, 10);

        return $this->store($look->id, $hash.'.css', fn () => Decorations::css($look, $parts));
    }

    /** A file shipped in the package, copied to assets/ under a name that changes with it. */
    protected function publish(string $source, string $name, string $ext): string
    {
        $hash = substr(md5(@filemtime($source).'-'.@filesize($source)), 0, 10);

        return $this->store($name, "$hash.$ext", fn () => (string) file_get_contents($source));
    }

    /**
     * Write a file to assets/ once, the first time a page needs it, and return
     * its URL. Writing a new version removes the old ones of the same name, so
     * changing a look's settings does not leave a trail of stale files.
     */
    protected function store(string $name, string $suffix, callable $contents): string
    {
        $dir = 'extensions/ernestdefoe-festoon';
        $path = "$dir/$name-$suffix";

        if (! $this->assets->exists($path)) {
            foreach ($this->assets->files($dir) as $old) {
                if (str_starts_with(basename($old), "$name-") && preg_match('/^[0-9a-f]{10}\./', substr(basename($old), strlen($name) + 1))) {
                    $this->assets->delete($old);
                }
            }
            $this->assets->put($path, $contents());
        }

        // Flarum's assets disk is a Cloud disk, which knows its public URL.
        return $this->assets instanceof Cloud ? $this->assets->url($path) : '/assets/'.$path;
    }

    /** The falling layer: empty spans, each with its own lane, speed and delay. */
    public static function falling(string $effect): string
    {
        $colors = [
            'leaves' => ['#c2551b', '#d98a1e', '#9e3d12'],
            'petals' => ['#f4a6c0', '#f7c6d6', '#e987a8'],
            'hearts' => ['#c2185b', '#f06292', '#f48fb1'],
            'confetti' => ['#c62828', '#c9a227', '#e8c547'],
        ];

        if (! in_array($effect, ['snow', 'leaves', 'petals', 'hearts', 'embers', 'confetti'], true)) {
            return '';
        }

        $count = $effect === 'snow' ? 28 : 16;
        $html = '<div class="Festoon-fall Festoon-fall--'.$effect.'" aria-hidden="true">';

        for ($i = 0; $i < $count; $i++) {
            // Spread by a fixed stride, not randomness: the same page renders
            // the same bytes, and lanes never bunch up.
            $style = sprintf(
                '--x:%d%%;--t:%ds;--d:-%.1fs;--dx:%dpx;--r:%ddeg;--s:%dpx',
                ($i * 37 + 11) % 100,
                ($effect === 'snow' ? 12 : 13) + $i % 7,
                fmod($i * 1.7, 18),
                ($i % 5 - 2) * ($effect === 'leaves' ? 40 : 16),
                in_array($effect, ['leaves', 'confetti', 'petals'], true) ? 180 + $i * 40 : 0,
                4 + $i % 4
            );
            if (isset($colors[$effect])) {
                $style .= ';--c:'.$colors[$effect][$i % 3];
            }
            $html .= '<i style="'.$style.'"></i>';
        }

        return $html.'</div>';
    }
}
