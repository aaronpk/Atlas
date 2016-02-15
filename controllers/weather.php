<?php

$app->get('/api/weather', function() use($app) {
  $params = $app->request()->params();

  if(k($params, 'latitude') !== null && k($params, 'longitude') !== null) {

    $lat = (float)$params['latitude'];
    $lng = (float)$params['longitude'];

    $weather = \p3k\Weather::weather_for_location($lat, $lng);

    if($weather) {
      json_response($app, $weather);
    } else {
      json_response($app, [
        'error' => 'not_found',
        'error_description' => 'No weather information was found for the requested location'
      ]);
    }
  } else {
    json_response($app, [
      'error' => 'invalid_request',
      'error_description' => 'Request was missing parameters'
    ], 400);
  }
});
