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

    $sunny = self::sun($data->lat, $data->lon);

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

  /**
   * Sunrise and sunset today at a location, and whether the sun is up now.
   *
   * sunrise, sunset and now are hours of the day in the location's timezone
   * (e.g. 5.37 is 05:22). In polar day or night there is no sunrise or sunset
   * and both are null. $timestamp is "now", for tests.
   */
  public static function sun($lat, $lng, $timezone=null, $timestamp=null) {
    if(!$timezone) {
      $timezone = \p3k\Timezone::timezone_for_location($lat, $lng);
    }
    $tz = new DateTimeZone($timezone);
    $now = new DateTime('@' . ($timestamp ?? time()));
    $now->setTimeZone($tz);

    // date_sun_info() takes the day from the timestamp's date in PHP's default
    // timezone, so ask for noon of the location's current date in that zone
    $noon = new DateTime($now->format('Y-m-d') . ' 12:00:00', new DateTimeZone(date_default_timezone_get()));
    $info = date_sun_info($noon->getTimestamp(), $lat, $lng);

    $hours = function($ts) use($tz) {
      if(!is_int($ts)) return null;
      $t = new DateTime('@' . $ts);
      $t->setTimeZone($tz);
      return round($t->format('G') + $t->format('i')/60 + $t->format('s')/3600, 2);
    };

    if(is_int($info['sunrise']) && is_int($info['sunset'])) {
      $light = ($info['sunrise'] <= $now->getTimestamp() && $now->getTimestamp() < $info['sunset']) ? 'day' : 'night';
    } else {
      // true: the sun is up all day; false: it never rises
      $light = $info['sunrise'] === true ? 'day' : 'night';
    }

    return [
      'sunrise' => $hours($info['sunrise']),
      'sunset' => $hours($info['sunset']),
      'now' => round($now->format('H') + ($now->format('i')/60), 2),
      'light' => $light,
      'timezone' => $timezone,
    ];
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

