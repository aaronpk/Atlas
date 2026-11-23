<?php
// Checks p3k\Weather::sun() against published sunrise/sunset times
// (timeanddate.com, which includes refraction like date_sun_info()).
//   php tests/sun.php
require __DIR__ . '/../vendor/autoload.php';

// [name, lat, lng, timezone, local date, sunrise, sunset] with times as H:MM local,
// or null for polar day/night
$cases = [
  ['Portland, summer solstice', 45.5118, -122.6433, 'America/Los_Angeles', '2026-06-21', '5:22', '21:03'],
  ['Portland, winter solstice', 45.5118, -122.6433, 'America/Los_Angeles', '2026-12-21', '7:47', '16:30'],
  ['Auckland, summer',         -36.8485, 174.7633, 'Pacific/Auckland',    '2026-12-21', '5:57', '20:39'],
  ['Reykjavik, equinox',        64.1466, -21.9426, 'Atlantic/Reykjavik',  '2026-03-20', '7:28', '19:43'],
  ['Tromsø, midnight sun',      69.6492,  18.9553, 'Europe/Oslo',         '2026-06-21', null, null],
  ['Tromsø, polar night',       69.6492,  18.9553, 'Europe/Oslo',         '2026-12-21', null, null],
];

$fail = 0; $n = 0;
function check($ok, $msg) {
  global $fail, $n;
  $n++;
  if(!$ok) { $fail++; echo "  FAIL $msg\n"; }
}
function minutes($hm) {
  list($h, $m) = explode(':', $hm);
  return $h * 60 + $m;
}

// The server's default timezone must not change the answer
foreach(['UTC', 'Pacific/Kiritimati', 'Pacific/Pago_Pago', 'America/Los_Angeles'] as $default) {
  date_default_timezone_set($default);
  foreach($cases as list($name, $lat, $lng, $zone, $date, $rise, $set)) {
    $at = function($time) use($date, $zone) {
      return (new DateTime("$date $time", new DateTimeZone($zone)))->getTimestamp();
    };
    $sun = p3k\Weather::sun($lat, $lng, $zone, $at('12:00'));
    $label = "$name (default timezone $default)";
    if($rise === null) {
      check($sun['sunrise'] === null && $sun['sunset'] === null, "$label: expected no sunrise/sunset, got " . json_encode($sun));
      $expect = strpos($name, 'midnight sun') ? 'day' : 'night';
      foreach(['00:30', '12:00', '23:30'] as $t)
        check(p3k\Weather::sun($lat, $lng, $zone, $at($t))['light'] === $expect, "$label: light at $t should be $expect");
      continue;
    }
    // Within 2 minutes: PHP's algorithm and timeanddate's differ by up to a
    // minute (Auckland's sunrise is 05:58:00 vs 5:57), the published times are
    // whole minutes, and Atlas rounds to 0.01 h (36 s)
    check(abs($sun['sunrise'] * 60 - minutes($rise)) <= 2, "$label: sunrise {$sun['sunrise']} vs $rise");
    check(abs($sun['sunset'] * 60 - minutes($set)) <= 2, "$label: sunset {$sun['sunset']} vs $set");
    check($sun['now'] == 12.0, "$label: now should be 12, got {$sun['now']}");
    // Day and night on either side of sunrise and sunset, and at night after midnight
    $r = minutes($rise); $s = minutes($set);
    $hm = function($min) { return sprintf('%d:%02d', intdiv($min, 60), $min % 60); };
    foreach([[$hm($r - 3), 'night'], [$hm($r + 3), 'day'], ['12:00', 'day'], [$hm($s - 3), 'day'], [$hm($s + 3), 'night'], ['0:30', 'night'], ['23:50', 'night']] as list($t, $want))
      check(p3k\Weather::sun($lat, $lng, $zone, $at($t))['light'] === $want, "$label: light at $t should be $want");
  }
}
echo ($fail ? "FAILED" : "ok") . ": $n checks, $fail failed (" . PHP_VERSION . ")\n";
exit($fail ? 1 : 0);
