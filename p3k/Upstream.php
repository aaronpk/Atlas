<?php
namespace p3k;

/**
 * Every URL Atlas fetches (geocoder, weather, map tiles, marker icons) passes
 * through here. Normally it is returned unchanged. When the ATLAS_UPSTREAM
 * environment variable is set, e.g. to http://127.0.0.1:18380, the URL is
 * rewritten to that server with the original host as the first path segment,
 * so tests can serve every upstream from one local fixture server:
 *
 *   https://geocode.arcgis.com/arcgis/rest/... -> http://127.0.0.1:18380/geocode.arcgis.com/arcgis/rest/...
 */
class Upstream {

  public static function url($url) {
    $base = getenv('ATLAS_UPSTREAM');
    if(!$base)
      return $url;
    return preg_replace('#^https?://#i', rtrim($base, '/') . '/', $url);
  }

}
