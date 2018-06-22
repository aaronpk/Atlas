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

  $data = [
    $line[13], // Code
    $line[4],  // Latitude
    $line[5],  // Longitude
    $line[3],  // Name
  ];

  $airports[] = $data;
}

fclose($fp);

unlink('airports.csv');

$fp = fopen('airports-compact.csv', 'w');

foreach($airports as $line) {
  fputcsv($fp, $line);
}

fclose($fp);

echo "Done\n";
