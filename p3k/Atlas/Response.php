<?php
namespace p3k\Atlas;

/**
 * A minimal HTTP response: status, headers and a body, sent with send().
 */
class Response {
  public $status;
  public $headers;
  public $body;

  public function __construct($body = '', $status = 200, $headers = []) {
    $this->body = $body;
    $this->status = $status;
    $this->headers = $headers;
  }

  public static function html($html, $status = 200) {
    return new self($html, $status, ['Content-Type' => 'text/html; charset=utf-8']);
  }

  public static function json($data, $status = 200) {
    return new self(json_encode($data), $status, ['Content-Type' => 'application/json']);
  }

  public static function image($data, $contentType) {
    return new self($data, 200, [
      'Content-Type' => $contentType,
      'Cache-Control' => 'max-age=' . (60*60*24*30) . ', public',
    ]);
  }

  public static function text($text, $status = 200) {
    return new self($text, $status, ['Content-Type' => 'text/plain; charset=utf-8']);
  }

  public function send() {
    http_response_code($this->status);
    foreach($this->headers as $name => $value) {
      header($name . ': ' . $value);
    }
    echo $this->body;
  }
}
