<?php
/*
 * Analiza los colores de todas las imágenes no analizadas.
 *
 **/
include_once('./config.php');
include_once(ROOT . 'Lib' . DS . 'ColorPalette.php');

$photos = $db->Query('SELECT * FROM photos WHERE id NOT IN (SELECT DISTINCT(photo_id) FROM photo_colors)');
$totalPhotos = count($photos);
$i = 0;

foreach ($photos as $photo) {
	$i++;

	echo "-----------------------------------\n";
	echo "[{$i}/{$totalPhotos}] Analyzing {$photo['thumbnail']} ...\n";

	$imagePath = PHOTOS_PATH . $photo['category'] . DS . $photo['thumbnail'];
	$CP = new ColorPalette($imagePath);
	$colors = $CP->extract(5); 
	$colorsList = array();
	$photoColors = array();

	foreach ($colors as $hex => $colorInfo) {
		$colorsList[] = $hex;
		$photoColors[] = array(
			'photo_id' => $photo['id'],
			'hex' => $hex,
			'amount' => $colorInfo['amount'],
			'percent' => $colorInfo['percent'],
			'red' => $colorInfo['RGB']['red'],
			'green' => $colorInfo['RGB']['green'],
			'blue' => $colorInfo['RGB']['blue'],
			'l' => $colorInfo['LAB']['l'],
			'a' => $colorInfo['LAB']['a'],
			'b' => $colorInfo['LAB']['b'],
			'hue' => $colorInfo['HSV']['hue'],
			'sat' => $colorInfo['HSV']['sat'],
			'val' => $colorInfo['HSV']['val']
		);
	}

	$insert = $db->Insert('photo_colors', $photoColors);

	echo '#' . count($colorsList) . ' colours registered: ' . implode(', ', $colorsList) . "\n";
	echo "-----------------------------------\n";
}