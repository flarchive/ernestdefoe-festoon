<?php

namespace Ernestdefoe\Festoon;

use Ernestdefoe\Festoon\Look\Catalogue;
use Ernestdefoe\Festoon\Look\Look;
use Flarum\Settings\SettingsRepositoryInterface;

/**
 * Festoon's settings, read once and cleaned.
 *
 * Everything lives in a handful of settings rows, which Flarum loads in one
 * query for the whole request anyway — so choosing today's look costs no
 * query of its own.
 */
class Config
{
    public const ENABLED = 'ernestdefoe-festoon.enabled';
    public const TIMEZONE = 'ernestdefoe-festoon.timezone';
    public const LOOKS = 'ernestdefoe-festoon.looks';
    public const CUSTOM = 'ernestdefoe-festoon.custom';
    public const PARTS = 'ernestdefoe-festoon.parts';

    /** Where decorations can go; each can be switched off forum-wide. */
    public const PART_NAMES = ['colors', 'header', 'effects', 'hats', 'ornaments', 'trims'];

    /** @var array<string, Look>|null */
    protected ?array $looks = null;

    public function __construct(protected SettingsRepositoryInterface $settings)
    {
    }

    public function enabled(): bool
    {
        return (bool) $this->settings->get(self::ENABLED, true);
    }

    /**
     * The forum's timezone for deciding what day it is. Falls back to Logo
     * Manager's seasonal timezone when that is set, so the two change over at
     * the same midnight, and then to the server's.
     */
    public function timezone(): string
    {
        $tz = (string) $this->settings->get(self::TIMEZONE, '');

        if ($tz === '') {
            $logoManager = json_decode((string) $this->settings->get('ernestdefoe-logo-manager.config', ''), true);
            $tz = (string) ($logoManager['seasons']['timezone'] ?? '');
        }

        return in_array($tz, timezone_identifiers_list(), true) ? $tz : date_default_timezone_get();
    }

    /** @return array<string, bool> */
    public function parts(): array
    {
        $stored = $this->json(self::PARTS);
        $parts = [];

        foreach (self::PART_NAMES as $name) {
            $parts[$name] = (bool) ($stored[$name] ?? true);
        }

        return $parts;
    }

    /** Every look, built-in and the administrator's own, keyed by id. @return array<string, Look> */
    public function looks(): array
    {
        if ($this->looks !== null) {
            return $this->looks;
        }

        $overrides = $this->json(self::LOOKS);
        $this->looks = [];

        foreach (Catalogue::all() as $key => $def) {
            $this->looks[$key] = Look::make($key, $def, is_array($overrides[$key] ?? null) ? $overrides[$key] : []);
        }

        foreach ($this->json(self::CUSTOM) as $def) {
            if (! is_array($def)) {
                continue;
            }

            $id = 'custom-'.preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($def['id'] ?? '')));

            if ($id !== 'custom-' && ! isset($this->looks[$id])) {
                $this->looks[$id] = Look::make($id, ['group' => 'custom'] + Look::CUSTOM_DEFAULTS, $def);
            }
        }

        return $this->looks;
    }

    public function look(string $id): ?Look
    {
        return $this->looks()[$id] ?? null;
    }

    /** @return array<mixed> */
    protected function json(string $key): array
    {
        $value = json_decode((string) $this->settings->get($key, ''), true);

        return is_array($value) ? $value : [];
    }
}
