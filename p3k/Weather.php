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
      'num' => (double)$data->current->temp,
      'unit' => '°F'
    ];
    $weather['feelslike'] = [
      'num' => (double)$data->current->feels_like,
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
    curl_setopt($ch, CURLOPT_URL, 'https://api.openweathermap.org/data/3.0/onecall?'.http_build_query($params));
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

    $sunrise = date_sunrise($now, SUNFUNCS_RET_DOUBLE, $lat, $lng, 90, $offset);
    $sunset = date_sunset($now, SUNFUNCS_RET_DOUBLE, $lat, $lng, 90, $offset);

    return [
      'sunrise' => round($sunrise,2),
      'sunset' => round($sunset,2),
      'now' => round($now,2),
      'light' => ($sunrise < $now && $now < $sunset) ? 'day' : 'night',
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

