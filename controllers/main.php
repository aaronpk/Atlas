<?php
use \Slim\Savant;

$app->get('/', function() use($app) {
  return Savant\render('index');
});

$app->get('/static-maps', function() use($app) {
  return Savant\render('static-maps');
});

$app->get('/map', function() use($app) {
  return Savant\render('map');
});

$app->map('/map/img', function() use($app) {
  $params = $app->request()->params();
  $app->response['Content-type'] = 'image/png';
  $assetPath = dirname(__FILE__) . '/../public/map-images';
  $map = p3k\geo\StaticMap\generate($params, null, $assetPath);
})->via('GET','POST');
