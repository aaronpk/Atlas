<?php
chdir('..');
require 'vendor/autoload.php';

\Slim\Savant\init();

require 'controllers/main.php';
require 'controllers/timezone.php';
require 'controllers/geocode.php';

$app->run();
