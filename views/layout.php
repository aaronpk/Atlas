<!doctype html>
<html>
  <head>
    <title>Atlas</title>
    <link rel="stylesheet" href="/assets/pure-0.6.0/pure-min.css">
    <link rel="stylesheet" href="/assets/pure-0.6.0/grids-responsive-min.css">
    <link rel="stylesheet" href="/assets/font-awesome-4.4.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="/assets/styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  <body>

    <header>
      <h1>Atlas</h1>
      <p>Atlas is a set of APIs for looking up information about locations.</p>
    </header>

    <?= $this->fetch($this->page . '.php') ?>

    <footer>
      <div class="right">
        <a href="https://github.com/aaronpk/Atlas">Source Code</a>
      </div>
      &copy; 2015 by Aaron Parecki
    </footer>
  </body>
</html>
