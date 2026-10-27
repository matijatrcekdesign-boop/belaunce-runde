<?php
/**
 * Enkratni razvojni pripomoček za 180 × 180 PNG ikono PWA.
 *
 * Zaženi: php orodja/izdelaj-ikono.php
 */

if (!extension_loaded('gd')) {
    fwrite(STDERR, "Za izdelavo ikone je potrebna razširitev GD.\n");
    exit(1);
}

$ikona = imagecreatetruecolor(180, 180);
$modra = imagecolorallocate($ikona, 42, 118, 224);
$bela = imagecolorallocate($ikona, 255, 255, 255);
imagefilledrectangle($ikona, 0, 0, 179, 179, $modra);

// Vgrajeno pisavo povečamo brez odvisnosti od lokalne TTF datoteke.
$crka = imagecreatetruecolor(40, 45);
$ozadje = imagecolorallocate($crka, 42, 118, 224);
$pisava = imagecolorallocate($crka, 255, 255, 255);
imagefilledrectangle($crka, 0, 0, 39, 44, $ozadje);
imagestring($crka, 5, 9, 13, 'B', $pisava);
imagecopyresized($ikona, $crka, 50, 35, 0, 0, 80, 110, 40, 45);

$cilj = __DIR__ . '/../paket/com_belauncerunde/media/images/apple-touch-icon.png';
imagepng($ikona, $cilj);
imagedestroy($crka);
imagedestroy($ikona);

echo "Ikona: {$cilj}\n";
