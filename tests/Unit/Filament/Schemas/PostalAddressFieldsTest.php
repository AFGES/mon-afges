<?php

declare(strict_types=1);

use App\Filament\Schemas\PostalAddressFields;
use EduardoRibeiroDev\FilamentLeaflet\ValueObjects\Address;
use EduardoRibeiroDev\FilamentLeaflet\ValueObjects\Coordinate;
use EduardoRibeiroDev\FilamentLeaflet\ValueObjects\GeoSearchResult;

/**
 * @param  array<string, string>  $address  Nominatim address details.
 */
function nominatimResult(array $address): GeoSearchResult
{
    return new GeoSearchResult(
        coordinate: new Coordinate(48.8685, 2.3318),
        type: 'house',
        addresstype: 'building',
        name: '',
        displayName: 'Somewhere, France',
        address: Address::fromArray($address),
        boundingbox: [],
    );
}

it('maps a search result to the address columns', function () {
    $result = nominatimResult([
        'house_number' => '12',
        'road' => 'Rue de la Paix',
        'postcode' => '75002',
        'city' => 'Paris',
        'country_code' => 'fr',
    ]);

    expect(PostalAddressFields::fromSearchResult($result))->toBe([
        'address_street' => '12 Rue de la Paix',
        'address_postcode' => '75002',
        'address_city' => 'Paris',
        'address_country' => 'FR',
        'address_latitude' => 48.8685,
        'address_longitude' => 2.3318,
    ]);
});

it('falls back to the town or village when there is no city', function (string $key) {
    $result = nominatimResult(['road' => 'Rue du Moulin', 'postcode' => '63000', $key => 'Somewhere']);

    expect(PostalAddressFields::fromSearchResult($result))
        ->address_city->toBe('Somewhere')
        ->address_street->toBe('Rue du Moulin');
})->with(['town', 'village']);

it('leaves missing parts empty, including the country', function () {
    $result = nominatimResult(['city' => 'Lyon']);

    expect(PostalAddressFields::fromSearchResult($result))->toBe([
        'address_street' => null,
        'address_postcode' => null,
        'address_city' => 'Lyon',
        'address_country' => null,
        'address_latitude' => 48.8685,
        'address_longitude' => 2.3318,
    ]);
});

it('drops a country code that is not two letters and truncates an oversized street', function () {
    $result = nominatimResult(['road' => str_repeat('a', 300), 'country_code' => 'france']);

    expect(PostalAddressFields::fromSearchResult($result))
        ->address_country->toBeNull()
        ->address_street->toHaveLength(255);
});
