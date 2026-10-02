<?php
namespace p3k;

class Airports {

  /**
   * Look up an airport by its IATA code, e.g. "PDX".
   *
   * Returns ['code', 'latitude', 'longitude', 'name'] or false when the code
   * is not in the bundled ourairports.com extract.
   */
  public static function from_code($code) {
    $code = strtoupper(trim((string)$code));
    if($code === '') {
      return false;
    }

    $fp = fopen(self::dataFile(), 'r');
    if(!$fp) {
      return false;
    }

    $airport = false;

    while(!$airport && ($line = fgetcsv($fp, null, ',', '"', '')) !== false) {
      if(isset($line[0]) && $line[0] == $code) {
        $airport = [
          'code' => $code,
          'latitude' => (float)$line[1],
          'longitude' => (float)$line[2],
          'name' => $line[3] ?? '',
        ];
      }
    }

    fclose($fp);

    return $airport;
  }

  public static function dataFile() {
    return dirname(__DIR__) . '/data/airports-compact.csv';
  }

}
