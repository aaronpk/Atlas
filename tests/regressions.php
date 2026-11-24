<?php
// Checks for bugs that predate the PHP 8 upgrade:
//  - tiles were placed 1px off on maps with an odd width or height
//  - 0 was treated as a missing latitude, longitude or zoom
//  - an invalid map width or height was a 500
//
//   php tests/regressions.php
// Starts tests/fixtures/server.php on 127.0.0.1:18382 for the upstream services.
require __DIR__ . '/../vendor/autoload.php';

$port = 18382;
$log = tempnam(sys_get_temp_dir(), 'atlas-fixture-');
$server = proc_open([PHP_BINARY, '-S', "127.0.0.1:$port", __DIR__ . '/fixtures/server.php'],
  [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
  $pipes, null, ['FIXTURE_LOG' => $log] + getenv());
register_shutdown_function(function() use($server, $log) { proc_terminate($server); @unlink($log); });
for($i = 0; $i < 50 && !@fsockopen('127.0.0.1', $port); $i++) usleep(100000);
putenv("ATLAS_UPSTREAM=http://127.0.0.1:$port");

$n = 0; $fail = 0;
function check($ok, $msg) {
  global $n, $fail;
  $n++;
  if(!$ok) { $fail++; echo "  FAIL $msg\n"; }
}
function upstream($log, $since) {
  return array_slice(file($log, FILE_IGNORE_NEW_LINES), $since);
}

// --- Tiles meet exactly ------------------------------------------------------------------------
// Each fixture tile has a dark line on its left/top edge and a red one on its
// right/bottom edge. Where two tiles meet, the red line must be immediately
// before the dark one; a 1px overlap paints over it. Dark lines are 256px apart.
const DARK = 40 << 16 | 40 << 8 | 40;
const RED = 200 << 16 | 40 << 8 | 40;
foreach([[777, 301], [333, 217], [301, 777], [600, 300], [512, 512], [999, 999]] as list($w, $h)) {
  $image = p3k\geo\StaticMap\render(['latitude' => '45.5165', 'longitude' => '-122.6764', 'zoom' => '12',
    'basemap' => 'streets', 'attribution' => 'none', 'width' => "$w", 'height' => "$h"]);
  $im = imagecreatefromstring($image['data']);
  check(imagesx($im) === $w && imagesy($im) === $h, "{$w}x{$h}: image size");
  foreach(['columns' => [$w, $h], 'rows' => [$h, $w]] as $dir => list($length, $across)) {
    $px = function($i, $at) use($im, $dir) { return $dir === 'columns' ? imagecolorat($im, $i, $at) : imagecolorat($im, $at, $i); };
    // A line to scan along that is not itself on a tile edge
    for($at = 5; $at < $across - 5; $at++) {
      if($px(1, $at) !== DARK && $px(1, $at) !== RED && $px(2, $at) !== DARK) break;
    }
    $dark = [];
    for($i = 0; $i < $length; $i++) if($px($i, $at) === DARK) $dark[] = $i;
    check(count($dark) > 0, "{$w}x{$h}: found a tile edge across the $dir");
    foreach($dark as $i => $x) {
      if($x > 0) check($px($x - 1, $at) === RED, "{$w}x{$h}: tile meeting at $dir position $x has its neighbour's last pixel (overlap?)");
      if($i > 0) check($x - $dark[$i - 1] === 256, "{$w}x{$h}: tile edges at {$dark[$i - 1]} and $x are not 256px apart");
    }
  }
}

// --- 0 is a value, not a missing parameter ---------------------------------------------------
$app = new p3k\Atlas\App(dirname(__DIR__));
$get = function($path, $params) use($app) {
  $r = $app->dispatch('GET', $path, $params);
  return [$r->status, json_decode($r->body, true)];
};

list($status, $body) = $get('/api/timezone', ['latitude' => '0', 'longitude' => '10']);
check($status === 200 && !empty($body['timezone']), "timezone at latitude 0: " . json_encode($body));
list($status, $body) = $get('/api/timezone', ['latitude' => '51.48', 'longitude' => '0']);
check($status === 200 && $body['timezone'] === 'Europe/London', "timezone at longitude 0 (Greenwich): " . json_encode($body));
list($status, $body) = $get('/api/timezone', ['latitude' => '', 'longitude' => '10']);
check($status === 400, "timezone with an empty latitude is still a 400");

$since = count(file($log));
list($status, $body) = $get('/api/geocode', ['latitude' => '51.48', 'longitude' => '0']);
check($status === 200 && $body['longitude'] == 0 && $body['latitude'] == 51.48 && $body['timezone'] === 'Europe/London', "reverse geocode at longitude 0: " . json_encode($body));
check((bool)preg_grep('#reverseGeocode\?location=0%2C51\.48&#', upstream($log, $since)), "reverse geocode asked the geocoder for 0,51.48");

list($status, $body) = $get('/api/weather', ['latitude' => '0', 'longitude' => '10', 'apikey' => 'good-key']);
check($status === 200 && isset($body['sun']), "weather at latitude 0: " . json_encode($body));

// Static maps: ArcGIS tile URLs end in /{z}/{y}/{x}
$tiles = function($params) use($log) {
  $since = count(file($log));
  p3k\geo\StaticMap\render($params + ['basemap' => 'streets', 'width' => '256', 'height' => '256']);
  return array_map(function($l) { return preg_replace('#^GET .*/tile/#', '', $l); }, upstream($log, $since));
};
// Latitude 0 off the coast of Ecuador: tiles around x=2, y=4 at zoom 3 (the
// center used to fall back to 0,0, tile x=4)
$t = $tiles(['latitude' => '0', 'longitude' => '-80', 'zoom' => '3']);
check(in_array('3/4/2', $t) || in_array('3/3/2', $t), "map centered at latitude 0, longitude -80: " . json_encode($t));
check(!in_array('3/4/4', $t), "map at latitude 0 does not fall back to the 0,0 center");
// zoom=0 is clamped to the minimum zoom (2) instead of being ignored (with a
// marker, an ignored zoom would fit the map to it at a high zoom instead)
$t = $tiles(['marker' => ['lat:45.5165;lng:-122.6764;icon:small-blue-cutout'], 'zoom' => '0']);
check(count($t) > 0 && count(preg_grep('#^2/#', $t)) === count($t), "zoom=0 draws at the minimum zoom 2: " . json_encode($t));
// maxzoom=0 caps the fitted zoom, then the minimum applies
$t = $tiles(['marker' => ['lat:45.5165;lng:-122.6764;icon:small-blue-cutout'], 'maxzoom' => '0']);
check(count($t) > 0 && count(preg_grep('#^2/#', $t)) === count($t), "maxzoom=0 caps the zoom at 2: " . json_encode($t));

// --- An invalid width or height is a 400, not a 500 -----------------------------------------------
$map = function($params) use($app) {
  $r = $app->dispatch('GET', '/map/img', $params);
  return [$r->status, $r->headers['Content-Type'] ?? '', $r->body];
};
foreach([['width' => 'abc'], ['width' => '-20'], ['width' => '0.5'], ['height' => 'abc'], ['width' => '300px'], ['height' => '-1']] as $bad) {
  $since = count(file($log));
  list($status, $type, $body) = $map($bad + ['latitude' => '45.5', 'longitude' => '-122.6', 'zoom' => '12',
    'marker' => ['location:Tokyo Tower;icon:small-red-cutout']]);
  $json = json_decode($body, true);
  $name = key($bad);
  check($status === 400 && $type === 'application/json' && ($json['error'] ?? '') === 'invalid_request'
    && strpos($json['error_description'] ?? '', $name) === 0, "$name=" . current($bad) . " is a 400 naming $name: $status $body");
  check(upstream($log, $since) === [], "$name=" . current($bad) . " is rejected before geocoding or fetching tiles");
}
$threw = false;
try { p3k\geo\StaticMap\render(['width' => 'abc']); } catch(InvalidArgumentException $e) { $threw = true; }
check($threw, "render() throws InvalidArgumentException for an invalid width");
// Still valid: fractional sizes round down, and 0 or empty means the default 300
foreach([['300.5', '200.5', 300, 200], ['0', '0', 300, 300], ['', '150', 300, 150], [' 64', '64 ', 64, 64]] as list($w, $h, $ew, $eh)) {
  list($status, $type, $body) = $map(['latitude' => '45.5', 'longitude' => '-122.6', 'zoom' => '12', 'width' => $w, 'height' => $h]);
  $im = $status === 200 ? imagecreatefromstring($body) : false;
  check($im && imagesx($im) === $ew && imagesy($im) === $eh, "width=" . json_encode($w) . " height=" . json_encode($h) . " renders {$ew}x{$eh}: $status");
}

echo ($fail ? "FAILED" : "ok") . ": $n checks, $fail failed (" . PHP_VERSION . ")\n";
exit($fail ? 1 : 0);
