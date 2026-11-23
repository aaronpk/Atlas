<?php
// Print an image's format, size and a hash of its decoded pixels, so images
// that look identical compare equal even when the encoder's bytes differ.
$data = file_get_contents($argv[1]);
$info = @getimagesizefromstring($data);
$im = @imagecreatefromstring($data);
if(!$info || !$im) {
  echo "body: not a decodable image (" . strlen($data) . " bytes)\n";
  exit;
}
$w = imagesx($im);
$h = imagesy($im);
$ctx = hash_init('md5');
for($y = 0; $y < $h; $y++) {
  $row = '';
  for($x = 0; $x < $w; $x++)
    $row .= pack('N', imagecolorat($im, $x, $y));
  hash_update($ctx, $row);
}
echo "body: {$info['mime']} {$w}x{$h} pixels=" . hash_final($ctx) . "\n";
