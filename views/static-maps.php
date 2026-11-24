<?php
$markdown = file_get_contents(__DIR__.'/../data/static-maps.md');
$html = Michelf\MarkdownExtra::defaultTransform($markdown);

// Give every heading an id and a link to itself, and collect the h2s for the
// table of contents
$toc = [];
$used = [];
$html = preg_replace_callback('#<h([23])>(.*?)</h\1>#', function($m) use(&$toc, &$used) {
  $text = strip_tags($m[2]);
  $id = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($text)), '-');
  for($n = 2, $base = $id; isset($used[$id]); $n++) $id = "$base-$n";
  $used[$id] = true;
  if($m[1] == '2') $toc[] = [$id, $text];
  return "<h{$m[1]} id=\"$id\">{$m[2]} <a class=\"anchor\" href=\"#$id\" aria-label=\"Link to this section\">#</a></h{$m[1]}>";
}, $html);

// List items written as "`name` - optional - text" or "`name` - default X - text"
// get the name and its default as separate labels
$html = preg_replace_callback('#<li><code>([^<]+)</code> - (?:(optional|default (?:<code>[^<]+</code>|[^ <]+)) - )?#', function($m) {
  $tag = isset($m[2]) && $m[2] !== '' ? ' <span class="tag">' . $m[2] . '</span>' : '';
  return '<li class="param"><code class="name">' . $m[1] . '</code>' . $tag . ' ';
}, $html);

// The built-in marker images are shown as a grid
$html = preg_replace('#(<h3 id="built-in-marker-images">.*?</h3>\s*)<ul>#s', '$1<ul class="icon-grid">', $html);
?>
<header class="hero">
  <div class="wrap">
    <h1>Static Maps</h1>
    <p class="lede">An API for generating map images with markers, paths and other overlays.</p>
  </div>
</header>

<div class="wrap docs-layout">
  <nav class="toc" aria-label="On this page">
    <p class="toc-title">On this page</p>
    <ul>
      <?php foreach($toc as list($id, $text)): ?>
        <li><a href="#<?= $id ?>"><?= htmlspecialchars($text) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <main class="docs">
    <?= $html ?>
  </main>
</div>
