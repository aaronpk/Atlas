<?php
namespace p3k;

class Airports {

  private static $fp = false;

  public static function from_code($code) {
    if(!self::$fp) {
      $fp = fopen(__DIR__.'/../data/airports-compact.csv', 'r');
    }

    rewind($fp);

    $airport = false;

    while(!$airport && ($line=fgetcsv($fp))) {
      if($line[0] == $code) {
        $airport = [
          'code' => $code,
          'latitude' => $line[1],
          'longitude' => $line[2],
          'name' => $line[3],
        ];
      }
    }

    return $airport;
  }

}
