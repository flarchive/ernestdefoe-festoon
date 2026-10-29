<?php

namespace Ernestdefoe\Festoon\Tests\integration\api;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class FestoonTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('ernestdefoe-festoon');
        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'quiet', 'email' => 'quiet@machine.local', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'is_email_confirmed' => 1, 'joined_at' => '2026-01-01 00:00:00', 'preferences' => json_encode(['festoonDecorations' => false])],
            ],
        ]);
    }

    private function page(?int $as = null, string $path = '/'): string
    {
        parse_str((string) parse_url($path, PHP_URL_QUERY), $query);
        $request = $this->request('GET', (string) parse_url($path, PHP_URL_PATH), $as ? ['authenticatedAs' => $as] : []);
        $response = $this->send($request->withQueryParams($query));
        $this->assertSame(200, $response->getStatusCode());

        return (string) $response->getBody();
    }

    /** Seasons cover the whole year, so some look is always up. */
    public function test_a_guest_gets_todays_colors_and_decorations(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('<style id="festoon-colors">', $html);
        $this->assertMatchesRegularExpression('#assets/extensions/ernestdefoe-festoon/[a-z0-9-]+\.css#', $html);
    }

    public function test_preview_is_for_admins_only(): void
    {
        $this->assertStringNotContainsString('--primary-color:#b71c1c', $this->page(2, '/?festoon=christmas'));
        $this->assertStringNotContainsString('--primary-color:#b71c1c', $this->page(null, '/?festoon=christmas'));
        $this->assertStringContainsString('--primary-color:#b71c1c', $this->page(1, '/?festoon=christmas'));
    }

    public function test_a_member_who_turned_decorations_off_keeps_the_colors_only(): void
    {
        $html = $this->page(3);

        $this->assertStringContainsString('Festoon-off', $html);
        $this->assertStringContainsString('<style id="festoon-colors">', $html);
        $this->assertStringNotContainsString('ernestdefoe-festoon/', $html);
        $this->assertStringNotContainsString('Festoon-fall', $html);
    }

    public function test_switched_off_forum_wide_adds_nothing(): void
    {
        $this->setting('ernestdefoe-festoon.enabled', '0');

        $html = $this->page();
        $this->assertStringNotContainsString('festoon-colors', $html);
        $this->assertStringNotContainsString('ernestdefoe-festoon/', $html);
    }

    /** Choosing and drawing today's look must not cost a single query. */
    public function test_no_queries_of_its_own(): void
    {
        $count = function (string $enabled, int $as) {
            $this->setting('ernestdefoe-festoon.enabled', $enabled);
            $this->page($as); // warm the decoration file and caches
            $db = $this->database();
            $db->flushQueryLog();
            $db->enableQueryLog();
            $this->page($as);
            $n = count($db->getQueryLog());
            $db->disableQueryLog();

            return $n;
        };

        foreach ([1, 2] as $as) {
            $this->assertSame($count('0', $as), $count('1', $as), "user $as");
        }
    }

    public function test_a_wrong_preview_name_is_ignored(): void
    {
        $this->assertStringContainsString('<style id="festoon-colors">', $this->page(1, '/?festoon=../../etc/passwd'));
    }
}
