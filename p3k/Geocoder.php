<?php
namespace p3k;

class Geocoder {

  public static function adrFromLocation($lat, $lng) {
    $response = self::_reverse($lat, $lng);

    if(!$response)
      return false;

    $address = $response->address;

    $result = new geocode\Result;

    $result->latitude = $lat;
    $result->longitude = $lng;

    if(property_exists($address, 'Postal'))
      $result->postalCode = $address->Postal;

    if(property_exists($address, 'Region'))
      $result->regionName = $address->Region;

    if(property_exists($address, 'City'))
      $result->localityName = $address->City;
    elseif(property_exists($address, 'Subregion'))
      $result->localityName = $address->Subregion;
    elseif(property_exists($address, 'Neighborhood'))
      $result->localityName = $address->Neighborhood;

    if(property_exists($address, 'CountryCode'))
      $result->countryName = $address->CountryCode;

    return $result;
  }

  public static function geocode($input) {
    $response = self::_geocode($input);

    if(!$response || count($response->locations) == 0)
      return false;

    $location = $response->locations[0];
    $attributes = $location->feature->attributes;
    $geometry = $location->feature->geometry;

    $result = new geocode\Result;

    if($geometry) {
      $result->latitude = $geometry->y;
      $result->longitude = $geometry->x;
    }

    if($attributes->City)
      $result->localityName = $attributes->City;

    if($attributes->Region)
      $result->regionName = $attributes->Region;

    if($attributes->Country)
      $result->countryName = $attributes->Country;

    if($attributes->Postal)
      $result->postalCode = $attributes->Postal;

    if($location->name)
      $result->fullAddress = $location->name;

    return $result;    
  }

  private static function _reverse($lat, $lng) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/reverseGeocode?location='.$lng.'%2C'.$lat.'&outSR=4326&f=json&distance=10000');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    // curl_setopt($ch, CURLOPT_USERAGENT, '');
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $result = curl_exec($ch);

    if($result == FALSE)
      return FALSE;

    return json_decode($result);    
  }

  private static function _geocode($input) {
    $ch = curl_init();

    $params = [
      'f' => 'json',
      'outSR' => 4326,
      'text' => $input,
      'outFields' => 'City,Region,Country,Postal',
    ];

    curl_setopt($ch, CURLOPT_URL, 'http://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/find?' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    // curl_setopt($ch, CURLOPT_USERAGENT, '');
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $result = curl_exec($ch);

    if($result == FALSE)
      return FALSE;

    return json_decode($result);    
  }

}
