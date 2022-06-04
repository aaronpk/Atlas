<header>
  <h1>Atlas Static Maps</h1>
  <p>Atlas Static Maps is an API for generating map images with markers or other overlays</p>
</header>


<div class="page docs">

<?php
$markdown = file_get_contents(__DIR__.'/../data/static-maps.md');
$html = Michelf\Markdown::defaultTransform($markdown);

echo $html;
?>

</div>
