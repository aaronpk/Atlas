<?php
namespace p3k\Atlas;

use p3k\Atlas\Controllers\Main;
use p3k\Atlas\Controllers\Timezone;
use p3k\Atlas\Controllers\Geocode;
use p3k\Atlas\Controllers\Weather;

/**
 * The Atlas web app: a route table, request parameters and the shared
 * helpers the controllers use. No framework.
 */
class App {
  private $root;
  private $view;

  public function __construct($root) {
    $this->root = rtrim($root, '/');
    $this->view = new View($this->root . '/views');
  }

  public function root() {
    return $this->root;
  }

  public function view() {
    return $this->view;
  }

  /**
   * [methods, path, [class, method]]
   */
  public function routes() {
    return [
      [['GET'], '/', [Main::class, 'index']],
      [['GET'], '/static-maps', [Main::class, 'staticMaps']],
      [['GET'], '/map', [Main::class, 'map']],
      [['GET', 'POST'], '/map/img', [Main::class, 'mapImage']],
      [['GET'], '/api/timezone', [Timezone::class, 'lookup']],
      [['GET'], '/api/geocode', [Geocode::class, 'lookup']],
      [['GET'], '/api/weather', [Weather::class, 'lookup']],
    ];
  }

  public function run() {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if($path !== '/') {
      $path = rtrim($path, '/');
    }
    $params = array_merge($_GET, $_POST);

    $this->dispatch($method, $path, $params)->send();
  }

  public function dispatch($method, $path, $params) {
    if($method === 'HEAD') {
      $method = 'GET';
    }
    foreach($this->routes() as list($methods, $route, $handler)) {
      if($route === $path) {
        if(!in_array($method, $methods)) {
          return Response::text('Method Not Allowed', 405);
        }
        list($class, $fn) = $handler;
        return $class::$fn($this, $params);
      }
    }
    return Response::text('Not Found', 404);
  }

  /**
   * The value of $a[$k] when it is set and truthy, else $default. The
   * controllers have always treated "" and "0" as missing.
   */
  public static function k($a, $k, $default = null) {
    if(is_array($k)) {
      $result = true;
      foreach($k as $key) {
        $result = $result && array_key_exists($key, $a);
      }
      return $result;
    }
    if(is_array($a) && array_key_exists($k, $a) && $a[$k])
      return $a[$k];
    elseif(is_object($a) && property_exists($a, $k) && $a->$k)
      return $a->$k;
    else
      return $default;
  }

  /**
   * Requests that carry one of the API keys listed in data/apikeys.txt may
   * use custom tile URLs and external marker icons.
   */
  public function isAuthenticated($params) {
    if(!isset($params['token']))
      return false;

    $tokenFile = $this->root . '/data/apikeys.txt';
    if(!file_exists($tokenFile))
      return false;

    $validTokens = array_map('trim', array_filter(file($tokenFile)));

    return in_array($params['token'], $validTokens, true);
  }
}
