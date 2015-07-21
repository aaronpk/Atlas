<?php
use \Slim\Savant;

$app->get('/', function() use($app) {
  return Savant\render('index');
});

$app->get('/map', function() use($app) {
  return Savant\render('map');
});
