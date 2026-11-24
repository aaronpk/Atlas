<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title><?= isset($title) ? htmlspecialchars($title) . ' · Atlas' : 'Atlas' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="description" content="Atlas: APIs for timezones, geocoding, weather and static map images.">
    <link rel="stylesheet" href="/assets/pure-0.6.0/pure-min.css">
    <link rel="stylesheet" href="/assets/font-awesome-4.4.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="/assets/styles.css">
  </head>
  <body>

    <nav class="topbar">
      <div class="wrap">
        <a class="brand" href="/"><i class="fa fa-compass"></i> Atlas</a>
        <div class="links">
          <a href="/"<?= ($nav ?? '') === 'home' ? ' aria-current="page"' : '' ?>>APIs</a>
          <a href="/static-maps"<?= ($nav ?? '') === 'static-maps' ? ' aria-current="page"' : '' ?>>Static Maps</a>
          <a href="https://github.com/aaronpk/Atlas"><i class="fa fa-github"></i> Source</a>
        </div>
      </div>
    </nav>

    <?= $content ?>

    <footer>
      <div class="wrap">
        <span>&copy; <?= date('Y') ?> Aaron Parecki</span>
        <a href="https://github.com/aaronpk/Atlas"><i class="fa fa-github"></i> Source code</a>
      </div>
    </footer>
  </body>
</html>
