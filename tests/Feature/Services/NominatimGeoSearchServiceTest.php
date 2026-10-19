<?php

declare(strict_types=1);

use EduardoRibeiroDev\FilamentLeaflet\Enums\GeoSearchProvider;
use EduardoRibeiroDev\FilamentLeaflet\Fields\GeoSearchInput;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * @return array<int, array<string, mixed>>
 */
function nominatimResponse(): array
{
    return [[
        'lat' => '48.8685',
        'lon' => '2.3318',
        'type' => 'house',
        'addresstype' => 'building',
        'name' => '',
        'display_name' => '12, Rue de la Paix, Paris, France',
        'address' => ['house_number' => '12', 'road' => 'Rue de la Paix', 'postcode' => '75002', 'city' => 'Paris'],
        'boundingbox' => [],
    ]];
}

/**
 * @return array<int, array{label: string, value: string}>
 */
function searchAddress(string $query, bool $cached = false): array
{
    $input = GeoSearchInput::make('address_search')
        ->provider(GeoSearchProvider::Nominatim)
        ->minSearchLength(3);

    if ($cached) {
        $input->cacheResults(60);
    }

    return $input->getSearchResults($query);
}

beforeEach(function (): void {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response(nominatimResponse())]);
});

it('identifies the application to Nominatim', function (): void {
    config(['app.name' => 'Plateforme', 'app.url' => 'https://example.test']);

    expect(searchAddress('12 rue de la Paix'))->toHaveCount(1);

    Http::assertSent(fn (Request $request): bool => $request->header('User-Agent') === ['Plateforme (https://example.test)']);
});

it('makes no request and returns nothing when a search was already made during the last second', function (): void {
    searchAddress('12 rue de la Paix');

    expect(searchAddress('14 rue de la Paix'))->toBe([]);

    Http::assertSentCount(1);
});

it('does not let cached searches consume the rate limit', function (): void {
    searchAddress('12 rue de la Paix', cached: true);

    expect(searchAddress('12 rue de la Paix', cached: true))->toHaveCount(1)
        ->and(searchAddress('12 rue de la Paix', cached: true))->toHaveCount(1);

    Http::assertSentCount(1);
});

it('does not cache the empty answer given while rate limited', function (): void {
    searchAddress('12 rue de la Paix');

    expect(searchAddress('14 rue de la Paix', cached: true))->toBe([]);

    $this->travel(2)->seconds();

    expect(searchAddress('14 rue de la Paix', cached: true))->toHaveCount(1);
});
