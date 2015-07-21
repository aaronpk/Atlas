<?php
namespace p3k\geocode;

class Result {
  public $latitude = null;
  public $longitude = null;
  public $fullAddress = null;
  public $localityName = null;
  public $regionName = null;
  public $countryName = null;
  public $postalCode = null;

  private function _full_name() {
    $parts = array();
    if($this->localityName)
      $parts[] = $this->localityName;
    if($this->regionName)
      $parts[] = $this->regionName;
    if($this->countryName)
      $parts[] = $this->countryName;
    return implode(', ', $parts);
  }

  private function _best_name() {
    if($this->localityName)
      return $this->localityName;
    if($this->regionName)
      return $this->regionName;
    if($this->countryName)
      return $this->countryName;
    return FALSE;
  }

  public function __get($key) {
    if($key == 'fullName')
      return $this->_full_name();
    if($key == 'bestName')
      return $this->_best_name();
    return NULL;
  }
}
