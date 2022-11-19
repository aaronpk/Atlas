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



## License

Available under the Apache 2.0 license. See [[LICENSE]].

Copyright 2015-2022 by Aaron Parecki.
