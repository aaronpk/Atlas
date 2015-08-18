<?php
namespace p3k\geo\WebMercator;

define('TILE_SIZE', 256);

function totalPixelsForZoomLevel($zoom) {
  return pow(2, $zoom) * TILE_SIZE;
}

function lngToX($longitude, $zoom) {
  return round((($longitude + 180) / 360) * totalPixelsForZoomLevel($zoom));
}

function latToY($latitude, $zoom) {
  return round(((atanh(sin(deg2rad(-$latitude))) / pi()) + 1) * totalPixelsForZoomLevel($zoom - 1));
}

function latLngToPixels($latitude, $longitude, $zoom) {
  return array(
    'x' => lngToX($longitude, $zoom),
    'y' => latToY($latitude, $zoom)
  );
}

function xToLng($x, $zoom) {
  return (($x * 360) / totalPixelsForZoomLevel($zoom)) - 180;
}

function yToLat($y, $zoom) {
  $a = pi() * (($y / totalPixelsForZoomLevel($zoom - 1)) - 1);
  return -1 * (rad2deg(asin(tanh($a))));
}

function pixelsToLatLng($x, $y, $zoom) {
  return array(
    'lat' => yToLat($y, $zoom),
    'lng' => xToLng($x, $zoom)
  );
}

function tileToPixels($x, $y) {
  return array(
    'x' => $x * TILE_SIZE,
    'y' => $y * TILE_SIZE
  );
}

function pixelsToTile($x, $y) {
  return array(
    'x' => floor($x / TILE_SIZE),
    'y' => floor($y / TILE_SIZE)
  );
}

function positionInTile($x, $y) {
  $tile = pixelsToTile($x, $y);
  return array(
    'x' => round(TILE_SIZE * (($x / TILE_SIZE) - $tile['x'])),
    'y' => round(TILE_SIZE * (($y / TILE_SIZE) - $tile['y']))
  );
}
