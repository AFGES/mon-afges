<?php

declare(strict_types=1);

namespace App\Services;

use EduardoRibeiroDev\FilamentLeaflet\Services\GeoSearchService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Address search that follows the Nominatim usage policy: an identifying User-Agent and at most one request per second.
 */
final class NominatimGeoSearchService extends GeoSearchService
{
    public const string RATE_LIMIT_KEY = 'nominatim-geo-search';

    private const int MAX_ATTEMPTS_PER_SECOND = 1;

    private const int DECAY_SECONDS = 1;

    /**
     * Cached lookups never reach Nominatim, so only uncached ones consume the application-wide limit.
     * Returning early (rather than from fetchResults) keeps the empty answer out of the cache.
     * The parent caches a Collection but declares an array return type, so a cache hit is unwrapped here.
     *
     * @return array<string, \EduardoRibeiroDev\FilamentLeaflet\ValueObjects\GeoSearchResult>
     */
    public function search(string $query): array
    {
        if (blank($query)) {
            return [];
        }

        if ($this->isCached($query)) {
            $cached = Cache::get($this->buildCacheKey($query));

            return $cached instanceof Collection ? $cached->all() : $cached;
        }

        $results = [];

        $allowed = RateLimiter::attempt(
            self::RATE_LIMIT_KEY,
            self::MAX_ATTEMPTS_PER_SECOND,
            function () use ($query, &$results): void {
                $results = parent::search($query);
            },
            self::DECAY_SECONDS,
        );

        if ($allowed === false) {
            Log::debug('Nominatim address search skipped: rate limit reached.');
        }

        return $results;
    }

    /**
     * @return array<string, string>
     */
    protected function buildHeaders(): array
    {
        return [
            ...parent::buildHeaders(),
            'User-Agent' => config('app.name').' ('.config('app.url').')',
        ];
    }

    private function isCached(string $query): bool
    {
        return $this->cacheTtl !== null && Cache::has($this->buildCacheKey($query));
    }
}
