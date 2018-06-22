<?php

// airports.csv is from
// http://ourairports.com/data/
// http://ourairports.com/data/airports.csv

echo "Downloading...\n";
file_put_contents('airports.csv', file_get_contents('http://ourairports.com/data/airports.csv'));

echo "Processing...\n";

$airports = [];

$fp = fopen('airports.csv', 'r');
while($line = fgetcsv($fp)) {
  if($line[0] == 'id') {
    $keys = $line;
    continue;
  }

  if($line[13] == '') continue;

  $airports[$line[13]] = [];
  foreach($keys as $i=>$k) {
    $airports[$line[13]][$k] = $line[$i];
  }

}

fclose($fp);

unlink('airports.csv');

file_put_contents('airports.json', json_encode($airports, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

echo "Done\n";
