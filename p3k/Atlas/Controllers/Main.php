<?php
namespace p3k\Atlas\Controllers;

use p3k\Atlas\App;
use p3k\Atlas\Response;

class Main {

  public static function index(App $app, $params) {
    return Response::html($app->view()->render('index', ['nav' => 'home']));
  }

  public static function staticMaps(App $app, $params) {
    return Response::html($app->view()->render('static-maps', ['title' => 'Static Maps API', 'nav' => 'static-maps']));
  }

  public static function map(App $app, $params) {
    return Response::html($app->view()->render('map', ['title' => 'Static Maps']));
  }

  public static function mapImage(App $app, $params) {
    $assetPath = $app->root() . '/public/map-images';
    try {
      $image = \p3k\geo\StaticMap\render($params, $assetPath, $app->isAuthenticated($params));
    } catch(\InvalidArgumentException $e) {
      return Response::json([
        'error' => 'invalid_request',
        'error_description' => $e->getMessage()
      ], 400);
    }
    return Response::image($image['data'], $image['contentType']);
  }

}
