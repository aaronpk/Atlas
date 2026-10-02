<?php
namespace p3k\Atlas\Controllers;

use p3k\Atlas\App;
use p3k\Atlas\Response;

class Main {

  public static function index(App $app, $params) {
    return Response::html($app->view()->render('index'));
  }

  public static function staticMaps(App $app, $params) {
    return Response::html($app->view()->render('static-maps'));
  }

  public static function map(App $app, $params) {
    return Response::html($app->view()->render('map'));
  }

  public static function mapImage(App $app, $params) {
    $assetPath = $app->root() . '/public/map-images';
    $image = \p3k\geo\StaticMap\render($params, $assetPath, $app->isAuthenticated($params));
    return Response::image($image['data'], $image['contentType']);
  }

}
