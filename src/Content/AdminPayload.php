<?php

namespace Ernestdefoe\Festoon\Content;

use Ernestdefoe\Festoon\Config;
use Ernestdefoe\Festoon\Look\Catalogue;
use Ernestdefoe\Festoon\Look\Schedule;
use Flarum\Frontend\Document;

/**
 * What the admin page needs that settings alone cannot give it: every look
 * with its defaults filled in, when each one next runs (worked out here, with
 * the same date code the forum uses), and which one is up today.
 */
class AdminPayload
{
    public function __construct(protected Config $config, protected Schedule $schedule)
    {
    }

    public function __invoke(Document $document): void
    {
        $document->payload['festoon'] = [
            'looks' => array_map(fn ($look) => $look->toArray(), array_values($this->config->looks())),
            'next' => $this->schedule->nextWindows(),
            'today' => $this->schedule->active()?->id,
            'timezone' => $this->config->timezone(),
            'timezones' => timezone_identifiers_list(),
            'options' => [
                'garland' => Catalogue::GARLANDS,
                'ornament' => Catalogue::ORNAMENTS,
                'corner' => Catalogue::CORNERS,
                'hat' => Catalogue::HATS,
                'effect' => Catalogue::EFFECTS,
            ],
            'intl' => extension_loaded('intl'),
        ];
    }
}
