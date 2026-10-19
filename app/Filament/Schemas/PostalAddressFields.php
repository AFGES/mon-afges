<?php

declare(strict_types=1);

namespace App\Filament\Schemas;

use EduardoRibeiroDev\FilamentLeaflet\Enums\GeoSearchProvider;
use EduardoRibeiroDev\FilamentLeaflet\Fields\GeoSearchInput;
use EduardoRibeiroDev\FilamentLeaflet\ValueObjects\GeoSearchResult;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/**
 * Structured postal address fields on the user, filled by an OpenStreetMap address search and correctable by hand.
 */
final class PostalAddressFields
{
    private const int STREET_MAX_LENGTH = 255;

    private const int POSTCODE_MAX_LENGTH = 20;

    private const int CITY_MAX_LENGTH = 255;

    private const int SEARCH_CACHE_SECONDS = 86400;

    private const int MIN_SEARCH_LENGTH = 5;

    /**
     * @param  bool  $required  Makes street, postcode and city mandatory.
     */
    public static function component(bool $required = false): Fieldset
    {
        return Fieldset::make(__('user.fields.postal_address'))
            ->columns(2)
            ->columnSpanFull()
            ->schema([
                GeoSearchInput::make('address_search')
                    ->label(__('user.fields.address_search'))
                    ->helperText(__('user.fields.address_search_helper'))
                    ->provider(GeoSearchProvider::Nominatim)
                    ->withAddressDetails()
                    ->language('fr')
                    ->countryCodes('fr')
                    ->cacheResults(self::SEARCH_CACHE_SECONDS)
                    ->minSearchLength(self::MIN_SEARCH_LENGTH)
                    ->afterStateHydrated(null)
                    ->dehydrated(false)
                    ->live()
                    ->columnSpanFull()
                    ->afterStateUpdated(function (Set $set, mixed $state): void {
                        if ($state instanceof GeoSearchResult) {
                            foreach (self::fromSearchResult($state) as $field => $value) {
                                $set($field, $value);
                            }
                        }
                    }),
                TextInput::make('address_street')
                    ->label(__('user.fields.address_street'))
                    ->prefixIcon(Heroicon::OutlinedMapPin)
                    ->maxLength(self::STREET_MAX_LENGTH)
                    ->required($required)
                    ->live(onBlur: true)
                    ->afterStateUpdated(self::forgetCoordinates(...))
                    ->columnSpanFull(),
                TextInput::make('address_complement')
                    ->label(__('user.fields.address_complement'))
                    ->placeholder(__('user.fields.address_complement_placeholder'))
                    ->maxLength(self::STREET_MAX_LENGTH)
                    ->columnSpanFull(),
                TextInput::make('address_postcode')
                    ->label(__('user.fields.address_postcode'))
                    ->maxLength(self::POSTCODE_MAX_LENGTH)
                    ->required($required)
                    ->live(onBlur: true)
                    ->afterStateUpdated(self::forgetCoordinates(...)),
                TextInput::make('address_city')
                    ->label(__('user.fields.address_city'))
                    ->maxLength(self::CITY_MAX_LENGTH)
                    ->required($required)
                    ->live(onBlur: true)
                    ->afterStateUpdated(self::forgetCoordinates(...)),
                Hidden::make('address_country')
                    ->nullable()
                    ->rules(['regex:/^[A-Z]{2}$/']),
                Hidden::make('address_latitude')
                    ->nullable()

                    ->rules(['numeric', 'between:-90,90']),
                Hidden::make('address_longitude')
                    ->nullable()

                    ->rules(['numeric', 'between:-180,180']),
            ]);
    }

    /**
     * Address columns derived from a selected search result.
     *
     * @return array{address_street: ?string, address_postcode: ?string, address_city: ?string, address_country: ?string, address_latitude: ?float, address_longitude: ?float}
     */
    public static function fromSearchResult(GeoSearchResult $result): array
    {
        $address = $result->address;

        $street = Str::of(($address->houseNumber ?? '').' '.($address->street ?? ''))->squish()->toString();

        $country = filled($address->countryCode) ? Str::upper($address->countryCode) : null;

        return [
            'address_street' => $street === '' ? null : Str::substr($street, 0, self::STREET_MAX_LENGTH),
            'address_postcode' => filled($address->postcode) ? Str::substr($address->postcode, 0, self::POSTCODE_MAX_LENGTH) : null,
            'address_city' => filled($address->city) ? Str::substr($address->city, 0, self::CITY_MAX_LENGTH) : null,
            'address_country' => $country !== null && Str::length($country) === 2 ? $country : null,
            'address_latitude' => $result->coordinate?->lat,
            'address_longitude' => $result->coordinate?->lng,
        ];
    }

    /**
     * Coordinates only describe a searched address, so a manual edit of the text invalidates them.
     */
    public static function forgetCoordinates(Set $set): void
    {
        $set('address_latitude', null);
        $set('address_longitude', null);
    }
}
