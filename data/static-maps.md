## Base URL

The base URL of the static maps API is:

```
https://atlas.p3k.io/map/img
```

You can run your own instance of this for better performance.


## Parameters

Parameters can be included in either the query string or in the POST body.

* `zoom` - optional - Set the zoom level for the map. If not specified, a zoom level will be chosen that contains all markers on the map.
* `maxzoom` - optional - When a zoom level is chosen automatically, this sets an upper limit on the zoom level that will be chosen. Useful if you know your basemaps don't have imagery past a certain zoom level.
* `width` - default 300 - Width in pixels of the final image
* `height` - default 300 - Height in pixels of the final image
* `basemap` - default "streets" - Select the basemap
  * `streets` - Default [Esri street basemap](http://www.arcgis.com/home/webmap/viewer.html?webmap=7990d7ea55204450b8110d57e20c99ab)
  * `satellite` - Esri's [satellite basemap](http://www.arcgis.com/home/webmap/viewer.html?webmap=d802f08316e84c6592ef681c50178f17&center=-71.055499,42.364247&level=15)
  * `hybrid` - Satellite basemap with labels
  * `topo` - Esri [topographic map](http://www.arcgis.com/home/webmap/viewer.html?webmap=a72b0766aea04b48bf7a0e8c27ccc007)
  * `gray` - Esri gray canvas with labels
  * `gray-background` - Esri [gray canvas](http://www.arcgis.com/home/webmap/viewer.html?webmap=8b3d38c0819547faa83f7b7aca80bd76) without labels
  * `oceans` - Esri [ocean basemap](http://www.arcgis.com/home/webmap/viewer.html?webmap=5ae9e138a17842688b0b79283a4353f6&center=-122.255816,36.573652&level=8)
  * `national-geographic` - [National Geographic basemap](http://www.arcgis.com/home/webmap/viewer.html?webmap=d94dcdbe78e141c2b2d3a91d5ca8b9c9)
  * `osm` - [Open Street Map](http://www.openstreetmap.org/)
  * `stamen-toner` - [Stamen Toner](http://maps.stamen.com/toner/) black and white map with labels
  * `stamen-toner-background` - [Stamen Toner](http://maps.stamen.com/toner-background/) map without labels
  * `stamen-toner-lite` - [Stamen Toner Light](http://maps.stamen.com/toner-lite/) with labels
  * `stamen-terrain` - [Stamen Terrain](http://maps.stamen.com/terrain/) with labels
  * `stamen-terrain-background` - [Stamen Terrain](http://maps.stamen.com/terrain-background/) without labels
  * `stamen-watercolor` - [Stamen Watercolor](http://maps.stamen.com/watercolor/)
* `tileurl` - To use other map tiles, you can provide the tile URL pattern. Make sure to include the literal strings `{x}` `{y}` `{z}` in the URL which will be replaced with the appropriate tile number when generating the map
* `attribution` - default `none` - `none | esri | mapbox` - If you add attribution on the image in some other way, you can set this to "none", otherwise you can include the default esri or mapbox attributions
* `latitude` - optional - Latitude to center the map at. Not needed if using the location parameter, or if specifying one or more markers.
* `longitude` - optional - Longitude to center the map at.
* `location` - optional - Free-form text that will be geocoded to center the map. Not needed if specifying a location with the latitude and longitude parameters, or if a marker is specified.
* `marker[]` - Specify one or more markers to overlay on the map. Parameters are specified as: `key:value;`. See below for the full list of parameters.
* `path[]` - Specify one or more paths to draw on the map. See below for the full list of parameters to draw a path.
* `token` - To use external icons or tile URLs, provide an API key in the request. See below for documentation on configuring this.

## Markers

* `location` - Free-form text that will be geocoded to place the pin
* `lat` - If a `location` is not provided, you can specify the location with the `lat` and `lng` parameters.
* `lng` - See above
* `icon` - Icon to use for the marker. Must choose one of the icons provided in this library, or specify a full URL to a png image. If an invalid icon is specified, the marker will not be rendered.


### Built-In Marker Images

* ![dot-large-blue](map-images/dot-large-blue.png) `dot-large-blue`
* ![dot-large-gray](map-images/dot-large-gray.png) `dot-large-gray`
* ![dot-large-green](map-images/dot-large-green.png) `dot-large-green`
* ![dot-large-orange](map-images/dot-large-orange.png) `dot-large-orange`
* ![dot-large-pink](map-images/dot-large-pink.png) `dot-large-pink`
* ![dot-large-purple](map-images/dot-large-purple.png) `dot-large-purple`
* ![dot-large-red](map-images/dot-large-red.png) `dot-large-red`
* ![dot-large-yellow](map-images/dot-large-yellow.png) `dot-large-yellow`
* ![dot-small-blue](map-images/dot-small-blue.png) `dot-small-blue`
* ![dot-small-gray](map-images/dot-small-gray.png) `dot-small-gray`
* ![dot-small-green](map-images/dot-small-green.png) `dot-small-green`
* ![dot-small-orange](map-images/dot-small-orange.png) `dot-small-orange`
* ![dot-small-pink](map-images/dot-small-pink.png) `dot-small-pink`
* ![dot-small-purple](map-images/dot-small-purple.png) `dot-small-purple`
* ![dot-small-red](map-images/dot-small-red.png) `dot-small-red`
* ![dot-small-yellow](map-images/dot-small-yellow.png) `dot-small-yellow`
* ![fb](map-images/fb.png) `fb`
* ![google](map-images/google.png) `google`
* ![large-blue-blank](map-images/large-blue-blank.png) `large-blue-blank`
* ![large-blue-cutout](map-images/large-blue-cutout.png) `large-blue-cutout`
* ![large-gray-blank](map-images/large-gray-blank.png) `large-gray-blank`
* ![large-gray-cutout](map-images/large-gray-cutout.png) `large-gray-cutout`
* ![large-gray-user](map-images/large-gray-user.png) `large-gray-user`
* ![large-green-blank](map-images/large-green-blank.png) `large-green-blank`
* ![large-green-cutout](map-images/large-green-cutout.png) `large-green-cutout`
* ![large-orange-blank](map-images/large-orange-blank.png) `large-orange-blank`
* ![large-orange-cutout](map-images/large-orange-cutout.png) `large-orange-cutout`
* ![large-pink-blank](map-images/large-pink-blank.png) `large-pink-blank`
* ![large-pink-cutout](map-images/large-pink-cutout.png) `large-pink-cutout`
* ![large-purple-blank](map-images/large-purple-blank.png) `large-purple-blank`
* ![large-purple-cutout](map-images/large-purple-cutout.png) `large-purple-cutout`
* ![large-red-blank](map-images/large-red-blank.png) `large-red-blank`
* ![large-red-cutout](map-images/large-red-cutout.png) `large-red-cutout`
* ![large-yellow-blank](map-images/large-yellow-blank.png) `large-yellow-blank`
* ![large-yellow-cutout](map-images/large-yellow-cutout.png) `large-yellow-cutout`
* ![large-yellow-message](map-images/large-yellow-message.png) `large-yellow-message`
* ![large-yellow-user](map-images/large-yellow-user.png) `large-yellow-user`
* ![small-blue-blank](map-images/small-blue-blank.png) `small-blue-blank`
* ![small-blue-cutout](map-images/small-blue-cutout.png) `small-blue-cutout`
* ![small-gray-blank](map-images/small-gray-blank.png) `small-gray-blank`
* ![small-gray-cutout](map-images/small-gray-cutout.png) `small-gray-cutout`
* ![small-gray-message](map-images/small-gray-message.png) `small-gray-message`
* ![small-gray-user](map-images/small-gray-user.png) `small-gray-user`
* ![small-green-blank](map-images/small-green-blank.png) `small-green-blank`
* ![small-green-cutout](map-images/small-green-cutout.png) `small-green-cutout`
* ![small-green-user](map-images/small-green-user.png) `small-green-user`
* ![small-orange-blank](map-images/small-orange-blank.png) `small-orange-blank`
* ![small-orange-cutout](map-images/small-orange-cutout.png) `small-orange-cutout`
* ![small-pink-blank](map-images/small-pink-blank.png) `small-pink-blank`
* ![small-pink-cutout](map-images/small-pink-cutout.png) `small-pink-cutout`
* ![small-pink-user](map-images/small-pink-user.png) `small-pink-user`
* ![small-purple-blank](map-images/small-purple-blank.png) `small-purple-blank`
* ![small-purple-cutout](map-images/small-purple-cutout.png) `small-purple-cutout`
* ![small-red-blank](map-images/small-red-blank.png) `small-red-blank`
* ![small-red-cutout](map-images/small-red-cutout.png) `small-red-cutout`
* ![small-yellow-blank](map-images/small-yellow-blank.png) `small-yellow-blank`
* ![small-yellow-cutout](map-images/small-yellow-cutout.png) `small-yellow-cutout`
* ![small-yellow-user](map-images/small-yellow-user.png) `small-yellow-user`


## Authentication

To be able to use externally-referenced icons or tile URLs, you will need to configure API keys and provide a token in the request. This locks down the ability to fetch external resources to only trusted users of the system.

Create a file `data/apikeys.txt` and generate a random string with a tool of your choosing, and with one API key per line. Any value passed in the parameter `token` that matches the text in a line in this file will enable the request to use the restricted features that reference external URLs.


## Paths

A path is specified as a list of longitude and latitudes, as well as optional properties to specify the weight and color of the path.

The coordinates of the path are the first value of the property, specified as a list of coordinates similar to GeoJSON.

### Examples

Simple path with default color and weight.

```
path[]=[-122.651082,45.508543],[-122.653617,45.506468],[-122.654183,45.506756]
```

Specifying the color and weight of the path.

```
path[]=[-122.651082,45.508543],[-122.653617,45.506468],[-122.654183,45.506756];weight:6;color:0033ff
```


## Examples

### Simple map centered at a location

```
https://atlas.p3k.io/map/img?basemap=gray&width=400&height=240&zoom=14&latitude=45.5165&longitude=-122.6764
```

<img src="/map/img?basemap=gray&width=400&height=240&zoom=14&latitude=45.5165&longitude=-122.6764">

### Map with a marker centered at an address

```
https://atlas.p3k.io/map/img?marker[]=location:920%20SW%203rd%20Ave,%20Portland,%20OR;icon:small-blue-cutout&basemap=gray&width=400&height=240&zoom=14
```

<img src="/map/img?marker[]=location:920%20SW%203rd%20Ave,%20Portland,%20OR;icon:small-blue-cutout&basemap=gray&width=400&height=240&zoom=14">
