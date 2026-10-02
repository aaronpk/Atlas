# Atlas

Atlas is a set of APIs to look up information about locations.

## Timezone

Retrieving the timezone at a lat/lng

* `/api/timezone?latitude=45.5118&amp;longitude=-122.6433`
* `/api/timezone?airport=PDX`

## Geocoder

Retrieving the lat/lng for a named location

* `/api/geocode?input=309+SW+6th+Ave,+Portland,+OR`

Retrieving a named location from a lat/lng

* `/api/geocode?latitude=45.5118&longitude=-122.6433`
* `/api/geocode?latitude=45.5118&longitude=-122.6433&date=2016-07-012T09:00:00Z` and return the local time of the given timestamp

## Weather

Retrieving the current weather for a lat/lng

* `/api/weather?latitude=45.5118&longitude=-122.6433&apikey=XXX`

You'll need to pass an OpenWeatherMap.org API key in the request. Icon names reference the [weather-icons](https://erikflowers.github.io/weather-icons/) icon font.

## Static Maps

* `/map/img?marker[]=lat:45.5165;lng:-122.6764;icon:small-blue-cutout&basemap=gray&width=600&height=240&zoom=14`

[Full Static Maps Docs](https://atlas.p3k.io/static-maps)


## Running the web app

Atlas has no framework dependencies. Point a web server at `public/` with
`index.php` as the front controller (an `.htaccess` for Apache is included;
for nginx use `try_files $uri /index.php?$args;`), then run `composer install`.

Requests carrying a `token` listed in `data/apikeys.txt` (one per line) may use
custom tile URLs (`basemap=custom&tileurl=...`) and external marker icons.

For development: `php -S 127.0.0.1:8080 -t public`


## Using Atlas as a library

```
composer require p3k/atlas
```

Static maps are rendered in memory; nothing is echoed and no headers are sent:

```php
$image = p3k\geo\StaticMap\render([
  'marker' => ['lat:45.5165;lng:-122.6764;icon:small-blue-cutout'],
  'basemap' => 'gray',
  'width' => 600,
  'height' => 240,
  'zoom' => 14,
]);
// $image['data'] is the PNG (or JPEG, with format=jpg), $image['contentType'] its MIME type
```

The bundled marker icons are used by default; pass a directory as the second
argument to use your own, and `true` as the third to allow custom tile URLs.

Airports and timezones:

```php
$airport = p3k\Airports::from_code('PDX');   // ['code','latitude','longitude','name'] or false
$timezone = p3k\Timezone::timezone_for_location($airport['latitude'], $airport['longitude']);
$result = new p3k\timezone\Result($timezone, '2026-10-02'); // ->name, ->offset ("-07:00"), ->seconds, ->localtime
```



## License

Available under the Apache 2.0 license. See [[LICENSE]].

Copyright 2015-2022 by Aaron Parecki.
