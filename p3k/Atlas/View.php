<?php
namespace p3k\Atlas;

/**
 * Plain PHP templates: the page is rendered first, then wrapped in layout.php
 * which receives it as $content. Templates get the variables passed in.
 */
class View {
  private $directory;

  public function __construct($directory) {
    $this->directory = rtrim($directory, '/');
  }

  public function render($page, $vars = []) {
    if(!preg_match('/^[a-z0-9_-]+$/i', $page)) {
      throw new \InvalidArgumentException('Invalid template name');
    }
    $content = $this->fetch($page, $vars);
    return $this->fetch('layout', ['content' => $content] + $vars);
  }

  public function fetch($page, $vars = []) {
    $file = $this->directory . '/' . $page . '.php';
    if(!is_file($file)) {
      throw new \RuntimeException('Template not found: ' . $page);
    }
    ob_start();
    (function($__file, $__vars) {
      extract($__vars, EXTR_SKIP);
      require $__file;
    })($file, $vars);
    return ob_get_clean();
  }
}
