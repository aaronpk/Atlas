<?php
namespace p3k\Atlas\Controllers;

use p3k\Atlas\App;
use p3k\Atlas\Response;

class Timezone {

  public static function forLocation($lat, $lng) {
    $tz = \p3k\Timezone::timezone_for_location($lat, $lng);
    $timezone = false;
    if($tz) {
      $timezone = new \p3k\timezone\Result($tz);
    }

    if($timezone) {
      return [
        'timezone' => $timezone->name,
        'offset' => $timezone->offset,
        'seconds' => $timezone->seconds,
        'localtime' => $timezone->localtime
      ];
    } else {
      return [
        'error' => 'not_found',
        'error_description' => 'No timezone was found for the requested location'
      ];
    }
  }

  public static function lookup(App $app, $params) {
    if(App::k($params, 'latitude') !== null && App::k($params, 'longitude') !== null) {

      $lat = (float)$params['latitude'];
      $lng = (float)$params['longitude'];

      return Response::json(self::forLocation($lat, $lng));

    } elseif(App::k($params, 'airport')) {

      $code = $params['airport'];

      $airport = \p3k\Airports::from_code($code);

      if($airport) {
        $result = self::forLocation($airport['latitude'], $airport['longitude']);

        if(!isset($result['error'])) {
          $result['airport'] = $airport;
        }

        return Response::json($result);

      } else {
        return Response::json([
          'error' => 'not_found',
          'error_description' => 'The airport code was not found'
        ]);
      }

    } else {
      return Response::json([
        'error' => 'invalid_request',
        'error_description' => 'Request was missing parameters'
      ], 400);
    }
  }

}
