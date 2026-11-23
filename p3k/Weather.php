<?php
namespace p3k;

use Config;
use DateTime, DateTimeZone;

class Weather {
  public static function weather_for_location($lat, $lng, $key) {
    if($key == 'XXX') {
      return ['error' => 'Please provide an api key from openweathermap.org'];
    }

    $data = self::_fetch($lat, $lng, $key);
    if(!$data) return null;

    if(!property_exists($data, 'current'))
      return null;

    $weather = [
      'description' => null,
      'icon' => [
        'name' => null
      ],
      'temp' => null,
      'feelslike' => null,
      'humidity' => null,
      'wind' => null,
      'pressure' => null,
      'precip_today' => null,
      'timezone' => [
        'offset' => null,
        'name' => null,
        'abbr' => null
      ]
    ];

    $sunny = self::_sunny($data->lat, $data->lon);

    $icon_name = self::_icon_name($data->current->weather[0]->id);

    $weather['sun'] = $sunny;

    $weather['description'] = $data->current->weather[0]->description;
    $weather['icon']['name'] = $icon_name;
    $weather['temp'] = [
      'num' => (float)$data->current->temp,
      'unit' => '°F'
    ];
    $weather['feelslike'] = [
      'num' => (float)$data->current->feels_like,
      'unit' => '°F'
    ];
    $weather['wind'] = [
      'num' => $data->current->wind_speed,
      'unit' => 'mph'
    ];
    $weather['pressure'] = [
      'num' => (int)$data->current->pressure,
      'unit' => 'mb'
    ];
    $weather['humidity'] = [
      'num' => round($data->current->humidity),
      'unit' => '%'
    ];

    $weather['timezone']['name'] = $data->timezone;
    $weather['timezone']['offset'] = $data->timezone_offset;

    #$weather['raw'] = $current;

    return $weather;
  }

  private static function _fetch($lat, $lng, $key) {
    $params = [
      'units' => 'imperial',
      'lat' => $lat,
      'lon' => $lng,
      'appid' => $key,
      'exclude' => 'minutely,hourly,daily,alerts'
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, \p3k\Upstream::url('https://api.openweathermap.org/data/3.0/onecall?'.http_build_query($params)));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    // curl_setopt($ch, CURLOPT_USERAGENT, '');
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $result = curl_exec($ch);

    if($result == FALSE)
      return FALSE;

    return json_decode($result);
  }

  // Returns "day" or "night" depending on whether the sun is up at the given location
  private static function _sunny($lat, $lng, $timezone=null) {
    // Get the beginning of the current day

    $now = new DateTime();
    if(!$timezone) {
      $timezone = \p3k\Timezone::timezone_for_location($lat, $lng);
    }
    $tz = new DateTimeZone($timezone);
    $now->setTimeZone($tz);
    $offset = $now->format('Z')/3600;
    $now = $now->format('H') + ($now->format('i')/60);

    // $now is the hour of the day, but it was always passed to date_sunrise()
    // as the timestamp, so these are the times for 1 January 1970.
    $sunrise = self::_sun_hours((int)$now, $lat, $lng, 90, $offset, false);
    $sunset = self::_sun_hours((int)$now, $lat, $lng, 90, $offset, true);

    return [
      'sunrise' => round($sunrise,2),
      'sunset' => round($sunset,2),
      'now' => round($now,2),
      'light' => ($sunrise < $now && $now < $sunset) ? 'day' : 'night',
      'timezone' => $timezone,
    ];
  }

  // date_sunrise()/date_sunset() with SUNFUNCS_RET_DOUBLE, which are deprecated
  // since PHP 8.1. date_sun_info() uses a different altitude, so this is the
  // same calculation ported from timelib (timelib_astro_rise_set_altitude):
  // the hour of sunrise or sunset at the given UTC offset, or false when the
  // sun does not cross the altitude that day. It matches the built-in
  // functions exactly, including the use of the default timezone to pick the day.
  private static function _sun_hours($timestamp, $lat, $lng, $zenith, $gmtOffset, $sunset) {
    $RADEG = 180.0 / M_PI;
    $DEGRAD = M_PI / 180.0;
    $rev = function($x) { return $x - 360.0 * floor($x * (1.0 / 360.0)); };
    $rev180 = function($x) { return $x - 360.0 * floor($x * (1.0 / 360.0) + 0.5); };

    // The local date of $timestamp in the default timezone, at 00:00 UTC
    $local = new DateTime('@' . $timestamp);
    $local->setTimezone(new DateTimeZone(date_default_timezone_get()));
    $utcMidnight = gmmktime(0, 0, 0, (int)$local->format('n'), (int)$local->format('j'), (int)$local->format('Y'));

    $altit = 90 - $zenith;

    // Days since 2000 Jan 0.0 at local noon
    $d = (((float)$utcMidnight / 86400.0 + 2440587.5) - 2451545) + 2 - $lng / 360.0;

    // Local sidereal time
    $sidtime = $rev($rev((180.0 + 356.0470 + 282.9404) + (0.9856002585 + 4.70935E-5) * $d) + 180.0 + $lng);

    // The sun's ecliptic longitude and distance
    $M = $rev(356.0470 + 0.9856002585 * $d);
    $w = 282.9404 + 4.70935E-5 * $d;
    $e = 0.016709 - 1.151E-9 * $d;
    $E = $M + $e * $RADEG * sin($M * $DEGRAD) * (1.0 + $e * cos($M * $DEGRAD));
    $x = cos($E * $DEGRAD) - $e;
    $y = sqrt(1.0 - $e * $e) * sin($E * $DEGRAD);
    $sr = sqrt($x * $x + $y * $y);
    $v = $RADEG * atan2($y, $x);
    $lon = $v + $w;
    if($lon >= 360.0) $lon -= 360.0;

    // Right ascension and declination
    $x = $sr * cos($lon * $DEGRAD);
    $y = $sr * sin($lon * $DEGRAD);
    $obl_ecl = 23.4393 - 3.563E-7 * $d;
    $z = $y * sin($obl_ecl * $DEGRAD);
    $y = $y * cos($obl_ecl * $DEGRAD);
    $sRA = $RADEG * atan2($y, $x);
    $sdec = $RADEG * atan2($z, sqrt($x * $x + $y * $y));

    // When the sun is due south, in hours UT, and the correction for its upper limb
    $tsouth = 12.0 - $rev180($sidtime - $sRA) / 15.0;
    $altit -= 0.2666 / $sr;

    $cost = (sin($altit * $DEGRAD) - sin($lat * $DEGRAD) * sin($sdec * $DEGRAD)) / (cos($lat * $DEGRAD) * cos($sdec * $DEGRAD));
    if($cost >= 1.0 || $cost <= -1.0)
      return false;
    $t = $RADEG * acos($cost) / 15.0;

    $N = ($sunset ? $tsouth + $t : $tsouth - $t) + $gmtOffset;
    if($N > 24 || $N < 0)
      $N -= floor($N / 24) * 24;
    return $N;
  }

  private static function _icon_name($icon) {
    return 'wi-owm-'.$icon;
    /*
    // A mapping of darksky to weather-icons is here https://erikflowers.github.io/weather-icons/api-list.html
    $map = [
      'clear-day' => 'day-sunny',
      'clear-night' => 'night-clear',
      'rain' => 'rain',
      'snow' => 'snow',
      'sleet' => 'sleet',
      'wind' => 'strong-wind',
      'fog' => 'day-haze',
      'cloudy' => 'cloudy',
      'partly-cloudy-day' => 'day-cloudy',
      'partly-cloudy-night' => 'night-cloudy',
      'hail' => 'day-hail',
      'thunderstorm' => 'thunderstorm',
      'tornado' => 'tornado',
    ];
    if(array_key_exists($icon, $map)) {
      return 'wi-'.$map[$icon];
    } else {
      return false;
    }
    */
  }

}

