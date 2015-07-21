<?php

$app->get('/api/geocode', function() use($app) {
  $params = $app->request()->params();

  if(
    (k($params, 'latitude') !== null && k($params, 'longitude') !== null)
    || k($params, 'input') !== null
  ) {

    $response = [
      'latitude' => null,
      'longitude' => null,
      'locality' => null,
      'region' => null,
      'country' => null,
      'best_name' => null,
      'full_name' => null,
      'timezone' => null,
      'offset' => null,
      'seconds' => null,
      'localtime' => null,
    ];

    if(k($params, 'input')) {
      $adr = p3k\Geocoder::geocode($params['input']);
    } else {
      $lat = (float)$params['latitude'];
      $lng = (float)$params['longitude'];
      $response['latitude'] = $lat;
      $response['longitude'] = $lng;
      $adr = p3k\Geocoder::adrFromLocation($lat, $lng);
    }

    if($adr) {
      $response['latitude'] = $adr->latitude;
      $response['longitude'] = $adr->longitude;
      $response['locality'] = $adr->localityName;
      $response['region'] = $adr->regionName;
      $response['country'] = $adr->countryName;
      $response['best_name'] = $adr->bestName;
      $response['full_name'] = $adr->fullName;
    }

    $timezone = p3k\Timezone::timezone_for_location($response['latitude'], $response['longitude']);

    if($timezone) {
      $response['timezone'] = $timezone->name;
      $response['offset'] = $timezone->offset;
      $response['seconds'] = $timezone->seconds;
      $response['localtime'] = $timezone->localtime;
    }

    json_response($app, $response);
  } else {
    json_response($app, [
      'error' => 'invalid_request', 
      'error_description' => 'Request was missing parameters'
    ], 400);    
  }
});
