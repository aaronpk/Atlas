<?php
namespace p3k\Atlas\Controllers;

use p3k\Atlas\App;
use p3k\Atlas\Response;

class Geocode {

  public static function lookup(App $app, $params) {
    if(
      (App::k($params, 'latitude') !== null && App::k($params, 'longitude') !== null)
      || App::k($params, 'input') !== null
    ) {

      $response = [
        'latitude' => null,
        'longitude' => null,
        'locality' => null,
        'region' => null,
        'country' => null,
        'best_name' => null,
        'full_name' => null,
        'postal-code' => null,
        'timezone' => null,
        'offset' => null,
        'seconds' => null,
        'localtime' => null,
      ];

      if(App::k($params, 'input')) {
        $adr = \p3k\Geocoder::geocode($params['input']);
      } else {
        $lat = (float)$params['latitude'];
        $lng = (float)$params['longitude'];
        $response['latitude'] = $lat;
        $response['longitude'] = $lng;
        $adr = \p3k\Geocoder::adrFromLocation($lat, $lng);
      }

      if($adr) {
        $response['latitude'] = $adr->latitude;
        $response['longitude'] = $adr->longitude;
        $response['locality'] = $adr->localityName;
        $response['region'] = $adr->regionName;
        $response['country'] = $adr->countryName;
        $response['best_name'] = $adr->bestName;
        $response['full_name'] = $adr->fullName;
        $response['postal-code'] = $adr->postalCode;
      }

      $timezone = false;
      if($response['latitude'] !== null && $response['longitude'] !== null) {
        $tz = \p3k\Timezone::timezone_for_location($response['latitude'], $response['longitude']);
        if($tz) {
          $timezone = new \p3k\timezone\Result($tz, App::k($params, 'date'));
        }
      }

      if($timezone) {
        $response['timezone'] = $timezone->name;
        $response['offset'] = $timezone->offset;
        $response['seconds'] = $timezone->seconds;
        $response['localtime'] = $timezone->localtime;
      }

      return Response::json($response);
    } else {
      return Response::json([
        'error' => 'invalid_request',
        'error_description' => 'Request was missing parameters'
      ], 400);
    }
  }

}
