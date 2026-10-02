<?php
namespace p3k\Atlas\Controllers;

use p3k\Atlas\App;
use p3k\Atlas\Response;

class Weather {

  public static function lookup(App $app, $params) {
    if(App::k($params, 'latitude') !== null && App::k($params, 'longitude') !== null && App::k($params, 'apikey') !== null) {

      $lat = (float)$params['latitude'];
      $lng = (float)$params['longitude'];
      $key = $params['apikey'];

      $weather = \p3k\Weather::weather_for_location($lat, $lng, $key);

      if($weather) {
        return Response::json($weather);
      } else {
        return Response::json([
          'error' => 'not_found',
          'error_description' => 'No weather information was found for the requested location, or you used an invalid API key'
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
