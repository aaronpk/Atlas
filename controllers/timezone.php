<?php

function timezone_for_location($lat, $lng) {

  $tz = \p3k\Timezone::timezone_for_location($lat, $lng);
  $timezone = false;
  if($tz) {
    $timezone = new p3k\timezone\Result($tz);
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

$app->get('/api/timezone', function() use($app) {
  $params = $app->request()->params();

  if(k($params, 'latitude') !== null && k($params, 'longitude') !== null) {

    $lat = (float)$params['latitude'];
    $lng = (float)$params['longitude'];

    $result = timezone_for_location($lat, $lng);
    json_response($app, $result);

  } elseif(k($params, 'airport')) {

    $code = $params['airport'];

    $airport = \p3k\Airports::from_code($code);

    if($airport) {
      $result = timezone_for_location($airport['latitude'], $airport['longitude']);

      if(!isset($result['error'])) {
        $result['airport'] = $airport;
      }

      json_response($app, $result);

    } else {
      json_response($app, [
        'error' => 'not_found',
        'error_description' => 'The airport code was not found'
      ]);
    }

  } else {
    json_response($app, [
      'error' => 'invalid_request',
      'error_description' => 'Request was missing parameters'
    ], 400);
  }
});

