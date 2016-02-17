<?php
namespace p3k;

use Config;
use DateTime, DateTimeZone;

class Weather {
  public static function weather_for_location($lat, $lng) {
    $data = self::_fetch($lat, $lng);
    if(!$data) return null;

    $current = $data->current_observation;

    $weather = [
      'description' => null,
      'icon' => [
        'url' => null,
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

    if($current) {

      $loc = $current->display_location;
      if($loc) {
        $weather['location'] = [
          'city' => $loc->city,
          'state' => $loc->state,
          'country' => $loc->country,
          'zip' => $loc->zip
        ];
        $sunny = self::_sunny($current->display_location->latitude, $current->display_location->longitude, $current->local_tz_long);
      } else {
        $sunny = 'day';
      }

      $icon_name = self::_icon_name($current->icon, $sunny);

      $weather['description'] = $current->weather;
      $weather['icon']['url'] = $current->icon_url;
      $weather['icon']['name'] = $icon_name;
      $weather['temp'] = [
        'num' => (double)$current->temp_f,
        'unit' => '°F'
      ];
      $weather['feelslike'] = [
        'num' => (double)$current->feelslike_f,
        'unit' => '°F'
      ];
      $weather['wind'] = [
        'num' => $current->wind_mph,
        'unit' => 'mph'
      ];
      $weather['pressure'] = [
        'num' => (int)$current->pressure_mb,
        'unit' => 'mb'
      ];
      $weather['precip_today'] = [
        'num' => (double)$current->precip_today_in,
        'unit' => 'in'
      ];
      $weather['humidity'] = [
        'num' => (int)str_replace('%','',$current->relative_humidity),
        'unit' => '%'
      ];

      $weather['timezone']['offset'] = $current->local_tz_offset;
      $weather['timezone']['name'] = $current->local_tz_long;
      $weather['timezone']['abbr'] = $current->local_tz_short;
    }

    #$weather['raw'] = $current;

    return $weather;
  }

  private static function _fetch($lat, $lng) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://api.wunderground.com/api/'.Config::$wundergroundAPIKey.'/conditions/q/'.$lat.','.$lng.'.json');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    // curl_setopt($ch, CURLOPT_USERAGENT, '');
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $result = curl_exec($ch);

    if($result == FALSE)
      return FALSE;

    return json_decode($result);
  }

  // Returns "day" or "night" depending on whether the sun is up at the given location
  private static function _sunny($lat, $lng, $timezone) {
    // Get the beginning of the current day

    $now = new DateTime();
    if($timezone) {
      $now->setTimeZone(new DateTimeZone($timezone));
    }
    $now->setTime(0,0,0);
    $now = $now->format('U');

    if($lat !== null) {
      $sunrise = date_sunrise($now, SUNFUNCS_RET_TIMESTAMP, $lat, $lng, 96);
      $sunset = date_sunset($now, SUNFUNCS_RET_TIMESTAMP, $lat, $lng, 92);

      if($sunrise < time() && time() < $sunset) {
        return 'day';
      } else {
        return 'night';
      }
    } else {
      return 'unknown';
    }
  }

  private static function _icon_name($icon, $sunny) {
    // This list is from http://www.wunderground.com/weather/api/d/docs?d=resources/icon-sets
    // A mapping of wunderground to weather-icons is here https://erikflowers.github.io/weather-icons/api-list.html
    $map = [
      'day' => [
        'chanceflurries' => 'snow-wind',
        'chancerain' => 'day-rain',
        'chancesleet' => 'sleet',
        'chancesnow' => 'snow',
        'chancetstorms' => 'thunderstorm',
        'clear' => 'day-sunny',
        'cloudy' => 'cloudy',
        'flurries' => 'snow-wind',
        'fog' => 'day-haze',
        'hazy' => 'day-haze',
        'mostlycloudy' => 'cloud',
        'mostlysunny' => 'day-sunny-overcast',
        'partlycloudy' => 'day-cloudy',
        'partlysunny' => 'day-sunny-overcast',
        'sleet' => 'sleet',
        'rain' => 'rain',
        'snow' => 'snow',
        'sunny' => 'day-sunny',
        'tstorms' => 'thunderstorm',
      ],
      'night' => [
        'chanceflurries' => 'night-snow-wind',
        'chancerain' => 'night-rain',
        'chancesleet' => 'night-alt-sleet',
        'chancesnow' => 'night-snow',
        'chancetstorms' => 'night-thunderstorm',
        'clear' => 'night-clear',
        'cloudy' => 'cloudy',
        'flurries' => 'night-alt-snow-wind',
        'fog' => 'night-fog',
        'hazy' => 'night-fog',
        'mostlycloudy' => 'night-alt-cloudy',
        'mostlysunny' => 'night-clear',
        'partlycloudy' => 'night-alt-partly-cloudy',
        'partlysunny' => 'night-alt-partly-cloudy',
        'sleet' => 'night-alt-sleet',
        'rain' => 'night-alt-showers',
        'snow' => 'night-alt-snow',
        'sunny' => 'night-clear',
        'tstorms' => 'night-alt-thunderstorm',
      ]
    ];
    if(array_key_exists($icon, $map[$sunny])) {
      return 'wi-'.$map[$sunny][$icon];
    } else {
      return false;
    }
  }

}
