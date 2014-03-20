<?php
/**
 * Analiza los colores de todas las imágenes no analizadas.
 * ALTER TABLE `photo_colors` ADD `FDDCE5` FLOAT(5) NOT NULL DEFAULT '0' ;
 */
include_once('./config.php');
include_once(ROOT . 'Lib' . DS . 'ColorPalette.php');

$colorPalette = $db->Query('SELECT * FROM colors');
$hexId = array();
$hexPalette = array();

foreach ($colorPalette as $color) {
	$hexId[$color['hex']] = $color['id'];
	$hexPalette[] = hexdec($color['hex']);
}
print_r($hexPalette);die;
ColorPalette::setPalette($hexPalette);

$photos = $db->Query('SELECT * FROM photos WHERE id NOT IN (SELECT photo_id FROM photo_colors)');
$totalPhotos = count($photos);
$i = 0;

foreach ($photos as $photo) {
	$i++;

	echo "-----------------------------------\n";
	echo "[{$i}/{$totalPhotos}] Analyzing {$photo['thumbnail']} ... \n";

	$imageDst = PHOTOS_PATH . $photo['category'] . DS . $photo['thumbnail'];

	if (!file_exists($imageDst)) {
		echo "Image not found: {$imageDst}\n";
		continue;
	}

	$CP = new ColorPalette($imageDst);
	$colors = $CP->extract(); 
	$theInsert = array()

	foreach ($colors as $hex => $percent) {
		$theInsert[] = array(
			'photo_id' => $photo['id'],
			'color_id' => $hexId[$hex],
			'percent' => $percent
		);
	}

	$insert = $db->Insert('photo_colors', $theInsert);	
	echo "[id:{$photo['id']}] Done!\n";
}
