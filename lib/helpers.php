<?php
error_reporting(E_ALL ^ E_DEPRECATED);

function json_response(&$app, $response, $code=200) {
  $app->response()->status($code);
  $app->response()['Content-Type'] = 'application/json';
  $app->response()->body(json_encode($response));
}

function k($a, $k, $default=null) {
  if(is_array($k)) {
    $result = true;
    foreach($k as $key) {
      $result = $result && array_key_exists($key, $a);
    }
    return $result;
  } else {
    if(is_array($a) && array_key_exists($k, $a) && $a[$k])
      return $a[$k];
    elseif(is_object($a) && property_exists($a, $k) && $a->$k)
      return $a->$k;
    else
      return $default;
  }
}

function is_authenticated($params) {
  if(!isset($params['token']))
    return false;

  $token_file = __DIR__.'/../data/apikeys.txt';

  if(!file_exists($token_file))
    return false;

  $valid_tokens = array_filter(file($token_file));

  if(in_array($params['token'], $valid_tokens))
    return true;

  return false;
}
