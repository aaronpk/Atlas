<?php
// Router script for `php -S 127.0.0.1:18380 tests/fixtures/server.php`.
// Stands in for every service Atlas fetches from, when Atlas runs with
// ATLAS_UPSTREAM=http://127.0.0.1:18380 (see p3k/Upstream.php). The original
// host is the first path segment. Every request is appended to $FIXTURE_LOG.

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);
parse_str(parse_url($uri, PHP_URL_QUERY) ?? '', $q);

if($log = getenv('FIXTURE_LOG'))
  file_put_contents($log, $_SERVER['REQUEST_METHOD'] . ' ' . $uri . "\n", FILE_APPEND);

function json($data, $code = 200) {
  http_response_code($code);
  header('Content-Type: application/json');
  echo is_string($data) ? $data : json_encode($data);
  exit;
}

// A 256x256 tile whose colors are derived from its URL, with a dark line on the
// left and top edges and a red one on the right and bottom edges, so misplaced,
// overlapping or missing tiles show up in the output
function tile($key, $alpha = false) {
  $h = md5($key);
  $im = imagecreatetruecolor(256, 256);
  if($alpha) {
    // Overlay tiles: transparent with a diagonal label-like stripe
    imagesavealpha($im, true);
    imagealphablending($im, false);
    imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
    $c = imagecolorallocatealpha($im, hexdec($h[0].$h[1]), hexdec($h[2].$h[3]), hexdec($h[4].$h[5]), 40);
    for($i = 0; $i < 256; $i++)
      imagefilledrectangle($im, $i, $i, min(255, $i + 6), $i, $c);
  } else {
    imagefill($im, 0, 0, imagecolorallocate($im, hexdec($h[0].$h[1]), hexdec($h[2].$h[3]), hexdec($h[4].$h[5])));
    $line = imagecolorallocate($im, 40, 40, 40);
    imageline($im, 0, 0, 255, 0, $line);
    imageline($im, 0, 0, 0, 255, $line);
    $edge = imagecolorallocate($im, 200, 40, 40);
    imageline($im, 255, 0, 255, 255, $edge);
    imageline($im, 0, 255, 255, 255, $edge);
    imagefilledellipse($im, 128, 128, 40, 40, imagecolorallocate($im, hexdec($h[6].$h[7]), hexdec($h[8].$h[9]), 200));
  }
  header('Content-Type: image/png');
  imagepng($im);
  exit;
}

// --- ArcGIS geocoder ------------------------------------------------------------
if(preg_match('#^/geocode\.arcgis\.com/arcgis/rest/services/World/GeocodeServer/find$#', $path)) {
  $text = $q['text'] ?? '';
  $places = [
    '309 SW 6th Ave, Portland, OR' => ['name' => '309 SW 6th Ave, Portland, Oregon, 97204', 'x' => -122.67651, 'y' => 45.52098,
      'attributes' => ['City' => 'Portland', 'Region' => 'Oregon', 'Country' => 'USA', 'Postal' => '97204']],
    // No postal code: Atlas reverse geocodes the point to find one
    'Mount Hood' => ['name' => 'Mount Hood, Oregon', 'x' => -121.69583, 'y' => 45.37361,
      'attributes' => ['City' => '', 'Region' => 'Oregon', 'Country' => 'USA', 'Postal' => '']],
    // Only a country
    'Iceland' => ['name' => 'Iceland', 'x' => -18.5, 'y' => 64.9,
      'attributes' => ['City' => '', 'Region' => '', 'Country' => 'ISL', 'Postal' => '']],
    'Tokyo Tower' => ['name' => 'Tokyo Tower, Minato, Tokyo, JPN', 'x' => 139.74544, 'y' => 35.65858,
      'attributes' => ['City' => 'Minato', 'Region' => 'Tokyo', 'Country' => 'JPN', 'Postal' => '105-0011']],
  ];
  if($text === 'upstream error')
    json('{"error":{"code":498,"message":"Invalid token."}}');
  if($text === 'broken json')
    json('{"locations": [', 200);
  if(!isset($places[$text]))
    json(['spatialReference' => ['wkid' => 4326], 'locations' => []]);
  $p = $places[$text];
  json(['spatialReference' => ['wkid' => 4326], 'locations' => [[
    'name' => $p['name'],
    'extent' => ['xmin' => $p['x'] - 0.01, 'ymin' => $p['y'] - 0.01, 'xmax' => $p['x'] + 0.01, 'ymax' => $p['y'] + 0.01],
    'feature' => ['geometry' => ['x' => $p['x'], 'y' => $p['y']], 'attributes' => $p['attributes']],
  ]]]);
}

if(preg_match('#^/geocode\.arcgis\.com/arcgis/rest/services/World/GeocodeServer/reverseGeocode$#', $path)) {
  list($lng, $lat) = array_map('floatval', explode(',', $q['location'] ?? '0,0') + [1 => 0]);
  // The middle of the Pacific: no address
  if($lng < -140 && $lng > -170)
    json(['error' => ['code' => 400, 'message' => 'Cannot perform query. Invalid query parameters.', 'details' => ['Unable to find address for the specified location.']]]);
  // Mount Hood: no City, only a Subregion
  if(abs($lng + 121.69583) < 0.01)
    json(['address' => ['Match_addr' => 'Clackamas County', 'Subregion' => 'Clackamas County', 'Region' => 'Oregon', 'Postal' => '97049', 'CountryCode' => 'USA'],
      'location' => ['x' => $lng, 'y' => $lat]]);
  // Antarctica: only a Neighborhood, no postal code or country
  if($lat < -60)
    json(['address' => ['Match_addr' => 'McMurdo', 'Neighborhood' => 'McMurdo'], 'location' => ['x' => $lng, 'y' => $lat]]);
  json(['address' => ['Match_addr' => 'SW 6th Ave', 'City' => 'Portland', 'Region' => 'Oregon', 'Postal' => '97204', 'CountryCode' => 'USA'],
    'location' => ['x' => $lng, 'y' => $lat]]);
}

// --- OpenWeatherMap -----------------------------------------------------------------
if($path === '/api.openweathermap.org/data/3.0/onecall') {
  if(($q['appid'] ?? '') !== 'good-key')
    json(['cod' => 401, 'message' => 'Invalid API key. Please see https://openweathermap.org/faq#error401 for more info.'], 401);
  $lat = (float)$q['lat']; $lon = (float)$q['lon'];
  json([
    'lat' => $lat, 'lon' => $lon,
    'timezone' => $lat > 0 ? 'America/Los_Angeles' : 'Pacific/Auckland',
    'timezone_offset' => $lat > 0 ? -28800 : 46800,
    'current' => [
      'dt' => 1768644000, 'temp' => 47.3, 'feels_like' => 44, 'pressure' => 1019.4, 'humidity' => 81.6,
      'wind_speed' => 5.75, 'weather' => [['id' => 803, 'main' => 'Clouds', 'description' => 'broken clouds', 'icon' => '04d']],
    ],
  ]);
}

// --- Marker icons ----------------------------------------------------------------------
if($path === '/icons.example/pin.png') {
  $im = imagecreatetruecolor(20, 30);
  imagefill($im, 0, 0, imagecolorallocate($im, 200, 30, 120));
  header('Content-Type: image/png');
  imagepng($im);
  exit;
}
if($path === '/icons.example/not-an-image.png') {
  header('Content-Type: text/html');
  echo "<html>nope</html>";
  exit;
}

// --- Map tiles ----------------------------------------------------------------------------
if(preg_match('#/(Reference|World_Light_Gray_Reference)/MapServer/tile/#', $path))
  tile($path, true);
if(preg_match('#^/[a-z0-9.-]+/.*/\d+/\d+/\d+(\.png)?$#', $path))
  tile($path);

http_response_code(404);
header('Content-Type: text/plain');
echo "fixture: not found\n";
