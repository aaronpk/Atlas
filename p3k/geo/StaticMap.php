<?php
namespace p3k\geo\StaticMap;
use p3k\geo\WebMercator, p3k\Geocoder;
use Imagick, ImagickPixel, ImagickDraw;

function generate($params, $filename, $assetPath) {

  $bounds = array(
    'minLat' => 90,
    'maxLat' => -90,
    'minLng' => 180,
    'maxLng' => -180
  );

  // If any markers are specified, choose a default lat/lng as the center of all the markers
  $markers = array();
  if($markersTemp=k($params,'marker')) {
    if(!is_array($markersTemp))
      $markersTemp = array($markersTemp);

    // If no latitude is set, use the center of all the markers
    foreach($markersTemp as $i=>$m) {
      if(preg_match_all('/(?P<k>[a-z]+):(?P<v>[^;]+)/', $m, $matches)) {
        $properties = array();
        foreach($matches['k'] as $j=>$key) {
          $properties[$key] = $matches['v'][$j];
        }

        // Skip invalid marker definitions, show error in a header
        if(array_key_exists('icon', $properties) && (
            (array_key_exists('lat', $properties) && array_key_exists('lng', $properties))
            || array_key_exists('location', $properties)
          )
        ) {

          // Geocode the provided location and return lat/lng
          if(array_key_exists('location', $properties)) {
            $result = Geocoder::geocode($properties['location']);
            if(!$result) {
              #header('X-Marker-' . ($i+1) . ': error geocoding location "' . $properties['location'] . '"');
              continue;
            }

            $properties['lat'] = $result->latitude;
            $properties['lng'] = $result->longitude;
          }

          if(preg_match('/https?:\/\/(.+)/', $properties['icon'], $match)) {
            // Looks like an external image, attempt to download it
            $ch = curl_init($properties['icon']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $img = curl_exec($ch);
            $properties['iconImg'] = @imagecreatefromstring($img);
            if(!$properties['iconImg']) {
              $properties['iconImg'] = false;
            }
          } else {
            $properties['iconImg'] = imagecreatefrompng($assetPath . '/' . $properties['icon'] . '.png');
          }

          if($properties['iconImg']) {
            $markers[] = $properties;
          }

          if($properties['lat'] < $bounds['minLat'])
            $bounds['minLat'] = $properties['lat'];

          if($properties['lat'] > $bounds['maxLat'])
            $bounds['maxLat'] = $properties['lat'];

          if($properties['lng'] < $bounds['minLng'])
            $bounds['minLng'] = $properties['lng'];

          if($properties['lng'] > $bounds['maxLng'])
            $bounds['maxLng'] = $properties['lng'];
        } else {
          #header('X-Marker-' . ($i+1) . ': missing icon, or lat/lng/location parameters');
        }
      }
    }
  }


  $paths = array();
  if($pathsTemp=k($params,'path')) {
    if(!is_array($pathsTemp))
      $pathsTemp = array($pathsTemp);

    foreach($pathsTemp as $i=>$path) {
      $properties = array();
      if(preg_match_all('/(?P<k>[a-z]+):(?P<v>[^;]+)/', $path, $matches)) {
        foreach($matches['k'] as $j=>$key) {
          $properties[$key] = $matches['v'][$j];
        }
      }

      // Set default color and weight if none specified
      if(!array_key_exists('color', $properties))
        $properties['color'] = '333333';
      if(!array_key_exists('weight', $properties))
        $properties['weight'] = 3;

      // Now parse the points into an array
      if(preg_match_all('/(?P<point>\[[0-9\.-]+,[0-9\.-]+\])/', $path, $matches)) {
        $properties['path'] = json_decode('[' . implode(',', $matches['point']) . ']');
        // Adjust the bounds to fit the path

        foreach($properties['path'] as $point) {
          if($point[1] < $bounds['minLat'])
            $bounds['minLat'] = $point[1];

          if($point[1] > $bounds['maxLat'])
            $bounds['maxLat'] = $point[1];

          if($point[0] < $bounds['minLng'])
            $bounds['minLng'] = $point[0];

          if($point[0] > $bounds['maxLng'])
            $bounds['maxLng'] = $point[0];
        }
      }

      if(array_key_exists('path', $properties))
        $paths[] = $properties;
    }
  }


  $defaultLatitude = $bounds['minLat'] + (($bounds['maxLat'] - $bounds['minLat']) / 2);
  $defaultLongitude = $bounds['minLng'] + (($bounds['maxLng'] - $bounds['minLng']) / 2);

  if(k($params,'latitude') !== false) {
    $latitude = k($params,'latitude');
    $longitude = k($params,'longitude');
  } elseif(k($params,'location') !== false) {
    $result = ArcGISGeocoder::geocode(k($params,'location'));
    if(!$result->success) {
      $latitude = $defaultLatitude;
      $longitude = $defaultLongitude;
      #header('X-Geocode: error');
      #header('X-Geocode-Result: ' . $result->raw);
    } else {
      $latitude = $result->latitude;
      $longitude = $result->longitude;
      #header('X-Geocode: success');
      #header('X-Geocode-Result: ' . $latitude . ', ' . $longitude);
    }
  } else {
    $latitude = $defaultLatitude;
    $longitude = $defaultLongitude;
  }


  $width = k($params, 'width', 300);
  $height = k($params, 'height', 300);


  // If no zoom is specified, choose a zoom level that will fit all the markers and the path
  if(k($params,'zoom')) {
    $zoom = k($params,'zoom');
  } else {

    // start at max zoom level (20)
    $fitZoom = 21;
    $doesNotFit = true;
    while($fitZoom > 1 && $doesNotFit) {
      $fitZoom--;

      $center = webmercator\latLngToPixels($latitude, $longitude, $fitZoom);

      $leftEdge = $center['x'] - $width/2;
      $topEdge = $center['y'] - $height/2;

      // check if the bounding rectangle fits within width/height
      $sw = webmercator\latLngToPixels($bounds['minLat'], $bounds['minLng'], $fitZoom);
      $ne = webmercator\latLngToPixels($bounds['maxLat'], $bounds['maxLng'], $fitZoom);

      // leave some padding around the objects
      $fitHeight = abs($ne['y'] - $sw['y']) + (0.1 * $height);
      $fitWidth = abs($ne['x'] - $sw['x']) + (0.1 * $width);

      if($fitHeight <= $height && $fitWidth <= $width) {
        $doesNotFit = false;
      }
    }

    $zoom = $fitZoom;
  }

  if(k($params,'maxzoom') && k($params,'maxzoom') < $zoom) {
    $zoom = k($params,'maxzoom');
  }

  $minZoom = 2;
  if($zoom < $minZoom)
    $zoom = $minZoom;

  $tileServices = array(
    'streets' => array(
      'https://services.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{Z}/{Y}/{X}'
    ),
    'satellite' => array(
      'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{Z}/{Y}/{X}'
    ),
    'hybrid' => array(
      'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{Z}/{Y}/{X}',
      'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{Z}/{Y}/{X}'
    ),
    'topo' => array(
      'http://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{Z}/{Y}/{X}'
    ),
    'gray' => array(
      'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{Z}/{Y}/{X}',
      'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Reference/MapServer/tile/{Z}/{Y}/{X}'
    ),
    'gray-background' => array(
      'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{Z}/{Y}/{X}',
    ),
    'oceans' => array(
      'https://server.arcgisonline.com/ArcGIS/rest/services/Ocean_Basemap/MapServer/tile/{Z}/{Y}/{X}'
    ),
    'national-geographic' => array(
      'https://server.arcgisonline.com/ArcGIS/rest/services/NatGeo_World_Map/MapServer/tile/{Z}/{Y}/{X}'
    ),
    'osm' => array(
      'https://tile.openstreetmap.org/{Z}/{X}/{Y}.png'
    ),
    'stamen-toner' => array(
      'http://tile.stamen.com/toner/{Z}/{X}/{Y}.png'
    ),
    'stamen-toner-background' => array(
      'http://tile.stamen.com/toner-background/{Z}/{X}/{Y}.png'
    ),
    'stamen-toner-lite' => array(
      'http://tile.stamen.com/toner-lite/{Z}/{X}/{Y}.png'
    ),
    'stamen-terrain' => array(
      'http://tile.stamen.com/terrain/{Z}/{X}/{Y}.png'
    ),
    'stamen-terrain-background' => array(
      'http://tile.stamen.com/terrain-background/{Z}/{X}/{Y}.png'
    ),
    'stamen-watercolor' => array(
      'http://tile.stamen.com/watercolor/{Z}/{X}/{Y}.png'
    ),
  );

  if(k($params,'basemap') && k($tileServices, k($params,'basemap'))) {
    $tileURL = $tileServices[k($params,'basemap')][0];
    if(array_key_exists(1, $tileServices[k($params,'basemap')]))
      $overlayURL = $tileServices[k($params,'basemap')][1];
    else
      $overlayURL = 0;
  } elseif(k($params, 'basemap') == 'custom') {
    $tileURL = $params['tileurl'];
    $overlayURL = false;
  } else {
    $tileURL = $tileServices['gray'][0];
    $overlayURL = false;
  }

  function urlForTile($x, $y, $z, $tileURL) {
    return str_replace(array(
      '{X}', '{Y}', '{Z}', '{x}', '{y}', '{z}'
    ), array(
      $x, $y, $z, $x, $y, $z
    ), $tileURL);
  }




  $im = imagecreatetruecolor($width, $height);

  // Find the pixel coordinate of the center of the map
  $center = webmercator\latLngToPixels($latitude, $longitude, $zoom);

  $leftEdge = $center['x'] - $width/2;
  $topEdge = $center['y'] - $height/2;

  $tilePos = webmercator\pixelsToTile($center['x'], $center['y']);
  // print_r($tilePos);
  // echo '<br />';

  $pos = webmercator\positionInTile($center['x'], $center['y']);
  // print_r($pos);
  // echo '<br />';

  // For the given number of pixels, determine how many tiles are needed in each direction
  $neTile = webmercator\pixelsToTile($center['x'] + $width/2, $center['y'] + $height/2);
  // print_r($neTile);
  // echo '<br />';

  $swTile = webmercator\pixelsToTile($center['x'] - $width/2, $center['y'] - $height/2);
  // print_r($swTile);
  // echo '<br />';


  // Now download all the tiles
  $tiles = array();
  $overlays = array();
  $chs = array();
  $mh = curl_multi_init();
  $numTiles = 0;

  $urls = array();

  for($x = $swTile['x']; $x <= $neTile['x']; $x++) {
    $x = (int)$x;
    if(!array_key_exists($x, $tiles)) {
      $tiles[$x] = array();
      $overlays[$x] = array();
      $chs[$x] = array();
      $ochs[$x] = array();
    }

    for($y = $swTile['y']; $y <= $neTile['y']; $y++) {
      $y = (int)$y;
      $url = urlForTile($x, $y, $zoom, $tileURL);
      $urls[] = $url;
      $tiles[$x][$y] = false;
      $chs[$x][$y] = curl_init($url);
      curl_setopt($chs[$x][$y], CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($chs[$x][$y], CURLOPT_FOLLOWLOCATION, true);
      curl_multi_add_handle($mh, $chs[$x][$y]);

      if($overlayURL) {
        $url = urlForTile($x, $y, $zoom, $overlayURL);
        $overlays[$x][$y] = false;
        $ochs[$x][$y] = curl_init($url);
        curl_setopt($ochs[$x][$y], CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ochs[$x][$y], CURLOPT_FOLLOWLOCATION, TRUE);
        curl_multi_add_handle($mh, $ochs[$x][$y]);
      }

      $numTiles++;
    }
  }

  $running = null;
  // Execute the handles. Blocks until all are finished.
  do {
    $mrc = curl_multi_exec($mh, $running);
  } while($running > 0);

  // In case any of the tiles fail, they will be grey instead of throwing an error
  $blank = imagecreatetruecolor(256, 256);
  $grey = imagecolorallocate($im, 224, 224, 224);
  imagefill($blank, 0,0, $grey);

  foreach($chs as $x=>$yTiles) {
    foreach($yTiles as $y=>$ch) {
      $content = curl_multi_getcontent($ch);
      if($content)
        $tiles[$x][$y] = @imagecreatefromstring($content);
      else
        $tiles[$x][$y] = $blank;
    }
  }

  if($overlayURL) {
    foreach($ochs as $x=>$yTiles) {
      foreach($yTiles as $y=>$ch) {
        $content = curl_multi_getcontent($ch);
        if($content)
          $overlays[$x][$y] = @imagecreatefromstring($content);
        else
          $overlays[$x][$y] = $blank;
      }
    }
  }

  // Assemble all the tiles into a new image positioned as appropriate
  foreach($tiles as $x=>$yTiles) {
    foreach($yTiles as $y=>$tile) {
      $x = intval($x);
      $y = intval($y);

      $ox = (($x - $tilePos['x']) * TILE_SIZE) - $pos['x'] + ($width/2);
      $oy = (($y - $tilePos['y']) * TILE_SIZE) - $pos['y'] + ($height/2);

      imagecopy($im, $tile, $ox,$oy, 0,0, imagesx($tile),imagesy($tile));
    }
  }

  if($overlayURL) {
    foreach($overlays as $x=>$yTiles) {
      foreach($yTiles as $y=>$tile) {
        $x = intval($x);
        $y = intval($y);

        $ox = (($x - $tilePos['x']) * TILE_SIZE) - $pos['x'] + ($width/2);
        $oy = (($y - $tilePos['y']) * TILE_SIZE) - $pos['y'] + ($height/2);

        imagecopy($im, $tile, $ox,$oy, 0,0, imagesx($tile),imagesy($tile));
      }
    }
  }


  if(count($paths)) {
    // Draw the path with ImageMagick because GD sucks as anti-aliased lines
    $mg = new Imagick();
    $mg->newImage($width, $height, new ImagickPixel('none'));

    $draw = new ImagickDraw();

    $colors = array();
    foreach($paths as $path) {

      $draw->setStrokeColor(new ImagickPixel('#'.$path['color']));
      $draw->setStrokeWidth($path['weight']);
      $draw->setFillOpacity(0);
      $draw->setStrokeLineCap(Imagick::LINECAP_ROUND);
      $draw->setStrokeLineJoin(Imagick::LINEJOIN_ROUND);

      $previous = false;
      foreach($path['path'] as $point) {
        if($previous) {
          $from = webmercator\latLngToPixels($previous[1], $previous[0], $zoom);
          $to = webmercator\latLngToPixels($point[1], $point[0], $zoom);

          if(k($params, 'bezier')) {

            $x_dist = abs($from['x'] - $to['x']);
            $y_dist = abs($from['y'] - $to['y']);

            // If the X distance is longer than Y distance, draw from left to right
            if($x_dist > $y_dist) {
              // Draw from left to right
              if($from['x'] > $to['x']) {
                $tmpFrom = $from;
                $tmpTo = $to;
                $from = $tmpTo;
                $to = $tmpFrom;
                unset($tmp);
              }
            } else {
              // Draw from top to bottom
              if($from['y'] > $to['y']) {
                $tmpFrom = $from;
                $tmpTo = $to;
                $from = $tmpTo;
                $to = $tmpFrom;
                unset($tmp);
              }
            }

            $angle = 1 * k($params, 'bezier');

            // Midpoint between the two ends
            $M = [
              'x' => ($from['x'] + $to['x']) / 2,
              'y' => ($from['y'] + $to['y']) / 2
            ];

            // Derived from http://math.stackexchange.com/a/383648 and http://www.wolframalpha.com/input/?i=triangle+%5B1,1%5D+%5B5,2%5D+%5B1-1%2Fsqrt(3),1%2B4%2Fsqrt(3)%5D

            // See  for details

            $A = $from;
            $B = $to;

            $P = [
              'x' => ($M['x']) - (($A['y']-$M['y']) * tan(deg2rad($angle))),
              'y' => ($M['y']) + (($A['x']-$M['x']) * tan(deg2rad($angle)))
            ];

            $draw->pathStart();
            $draw->pathMoveToAbsolute($A['x']-$leftEdge,$A['y']-$topEdge);
            $draw->pathCurveToQuadraticBezierAbsolute(
              $P['x']-$leftEdge, $P['y']-$topEdge,
              $B['x']-$leftEdge, $B['y']-$topEdge
            );
            $draw->pathFinish();
          } else {
            $draw->line($from['x']-$leftEdge,$from['y']-$topEdge, $to['x']-$leftEdge,$to['y']-$topEdge);
          }
        }
        $previous = $point;
      }
    }

    $mg->drawImage($draw);
    $mg->setImageFormat("png");

    $pathImg = imagecreatefromstring($mg);
    imagecopy($im, $pathImg, 0,0, 0,0, $width,$height);
  }


  // Add markers
  foreach($markers as $marker) {
    // Icons that have 'dot' in the name do not have a shadow and center vertically and horizontally
    $shadow = !preg_match('/dot/', $marker['icon']);

    if($width < 120 || $height < 120) {
      $shrinkFactor = 1.5;
    } else {
      $shrinkFactor = 1;
    }

    // Icons with a shadow are centered at the bottom middle pixel.
    // Icons with no shadow are centered in the center pixel.

    $px = webmercator\latLngToPixels($marker['lat'], $marker['lng'], $zoom);
    $pos = array(
      'x' => $px['x'] - $leftEdge,
      'y' => $px['y'] - $topEdge
    );

    if($shrinkFactor > 1) {
      $markerImg = imagecreatetruecolor(round(imagesx($marker['iconImg'])/$shrinkFactor), round(imagesy($marker['iconImg'])/$shrinkFactor));
      imagealphablending($markerImg, true);
      $color = imagecolorallocatealpha($markerImg, 0, 0, 0, 127);
      imagefill($markerImg, 0,0, $color);
      imagecopyresampled($markerImg, $marker['iconImg'], 0,0, 0,0, imagesx($markerImg),imagesy($markerImg), imagesx($marker['iconImg']),imagesy($marker['iconImg']));
    } else {
      $markerImg = $marker['iconImg'];
    }

    if($shadow) {
      $iconPos = array(
        'x' => $pos['x'] - round(imagesx($markerImg)/2),
        'y' => $pos['y'] - imagesy($markerImg)
      );
    } else {
      $iconPos = array(
        'x' => $pos['x'] - round(imagesx($markerImg)/2),
        'y' => $pos['y'] - round(imagesy($markerImg)/2)
      );
    }

    imagecopy($im, $markerImg, $iconPos['x'],$iconPos['y'], 0,0, imagesx($markerImg),imagesy($markerImg));
  }


  if(k($params,'attribution') == 'mapbox') {
    $logo = imagecreatefrompng($assetPath . '/mapbox-attribution.png');
    imagecopy($im, $logo, $width-imagesx($logo), $height-imagesy($logo), 0,0, imagesx($logo),imagesy($logo));
  } elseif(k($params,'attribution') == 'mapbox-small') {
    $logo = imagecreatefrompng($assetPath . '/mapbox-attribution.png');
    $shrinkFactor = 2;
    imagecopyresampled($im, $logo, $width-round(imagesx($logo)/$shrinkFactor), $height-round(imagesy($logo)/$shrinkFactor), 0,0, round(imagesx($logo)/$shrinkFactor),round(imagesy($logo)/$shrinkFactor), imagesx($logo),imagesy($logo));

  } elseif(k($params,'attribution') != 'none') {
    $logo = imagecreatefrompng($assetPath . '/powered-by-esri.png');

    // Shrink the logo if the image is small
    if($width > 120) {
      if($width < 220 || k($params, 'attribution') == 'small') {
        $shrinkFactor = 2;
        imagecopyresampled($im, $logo, $width-round(imagesx($logo)/$shrinkFactor)-4, $height-round(imagesy($logo)/$shrinkFactor)-4, 0,0, round(imagesx($logo)/$shrinkFactor),round(imagesy($logo)/$shrinkFactor), imagesx($logo),imagesy($logo));
      } else {
        imagecopy($im, $logo, $width-imagesx($logo)-4, $height-imagesy($logo)-4, 0,0, imagesx($logo),imagesy($logo));
      }
    }
  }


  #header('Cache-Control: max-age=' . (60*60*24*30) . ', public');
  #header('X-Tiles-Downloaded: ' . $numTiles);

  // TODO: add caching
  $fmt = k($params,'format', 'png');
  switch($fmt) {
    case "jpg":
    case "jpeg":
      header('Content-type: image/jpg');
      $quality = k($params, 'quality', 75);
      imagejpeg($im, $filename, $quality);
      break;
    case "png":
    default:
      header('Content-type: image/png');
      imagepng($im, $filename);
      break;
  }
  imagedestroy($im);

  /**
   * http://msdn.microsoft.com/en-us/library/bb259689.aspx
   * http://derickrethans.nl/php-mapping.html
   */
}

function k($a, $k, $default=false) {
  if(is_array($a) && array_key_exists($k, $a) && $a[$k])
    return $a[$k];
  elseif(is_object($a) && property_exists($a, $k) && $a->$k)
    return $a->$k;
  else
    return $default;
}
