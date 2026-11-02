<?php

/*
 * Festoon for Flarum 2 — a holiday theme that changes with the time of year.
 * Free and MIT licensed.
 *
 * Today's look is chosen on the server, in the forum's timezone, so the page
 * arrives already dressed. Colours are a small inline stylesheet; decorations
 * are a cached stylesheet file per look; the forum bundle is under 1 KB
 * gzipped, and the fireworks script is added only on days that have them.
 */

use Ernestdefoe\Festoon\Config;
use Ernestdefoe\Festoon\Content\AdminPayload;
use Ernestdefoe\Festoon\Content\InjectFestoon;
use Ernestdefoe\Festoon\Provider;
use Flarum\Extend;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->content(InjectFestoon::class),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less')
        ->content(AdminPayload::class),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Settings())
        ->default(Config::ENABLED, true),

    // A member's own switch for the decorations. Colours stay, so the forum
    // still matches the season; the garlands, hats and falling things go.
    (new Extend\User())
        ->registerPreference('festoonDecorations', fn ($value) => (bool) $value, true),

    (new Extend\ServiceProvider())
        ->register(Provider::class),
];
