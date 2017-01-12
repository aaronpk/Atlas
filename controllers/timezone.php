<?php

$app->get('/api/timezone', function() use($app) {
  $params = $app->request()->params();

  if(k($params, 'latitude') !== null && k($params, 'longitude') !== null) {

    $lat = (float)$params['latitude'];
    $lng = (float)$params['longitude'];

    $tz = \p3k\Timezone::timezone_for_location($lat, $lng);
    $timezone = false;
    if($tz) {
      $timezone = new p3k\timezone\Result($tz);
    }

    if($timezone) {
      json_response($app, [
        'timezone' => $timezone->name,
        'offset' => $timezone->offset,
        'seconds' => $timezone->seconds,
        'localtime' => $timezone->localtime
      ]);
    } else {
      json_response($app, [
        'error' => 'not_found', 
        'error_description' => 'No timezone was found for the requested location'
      ]);
    }
  } else {
    json_response($app, [
      'error' => 'invalid_request', 
      'error_description' => 'Request was missing parameters'
    ], 400);
  }
});

