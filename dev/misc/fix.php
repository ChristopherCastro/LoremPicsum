<?php
include_once('./config.php');

$errors = 0;
$photos = $db->Query('SELECT id, secret, thumbnail, category FROM photos WHERE 1');
echo "\n\nChecking images integrity (" . count($photos) . ")\n\n";
foreach ($photos as $photo) {
	$tn = PHOTOS_PATH . $photo['category'] . DS . $photo['thumbnail'];
	if (!file_exists($tn)) {
		echo "[photo_id:{$photo['id']}] Thumbnail not found: {$tn}\n";
		$errors++;
	}

	$tags_count = $db->Count('photo_tags', "photo_id = {$photo['id']}");
	if (!$tags_count) {
		echo "[photo_id:{$photo['id']}] 0 Tags found\n";
		$errors++;
	}

	$colors_count = $db->Count('photo_colors', "photo_id = {$photo['id']}");
	if (!$tags_count) {
		echo "[photo_id:{$photo['id']}] 0 Colors found\n";
		$errors++;
	}
}
echo !$errors ? "PASS!\n" : "ERROR\n";




$errors = 0;
echo "\n\nChecking colors integrity\n\n";
$limboColors = $db->Query('SELECT id, photo_id FROM photo_colors WHERE photo_id NOT IN (SELECT id FROM photos)');
foreach ($limboColors as $color) {
	echo "[color_id:{$color['id']}] Color pointing to unexisting photo {$color['photo_id']}\n";
	$errors++;
}
echo !$errors ? "PASS!\n" : "ERROR\n";




$errors = 0;
echo "\n\nChecking tags integrity\n\n";
$limboTags = $db->Query('SELECT id, photo_id FROM photo_tags WHERE photo_id NOT IN (SELECT id FROM photos)');
foreach ($limboTags as $tag) {
	echo "[tag_id:{$tag['id']}] Tag pointing to unexisting photo {$tag['photo_id']}\n";
	$errors++;
}
echo !$errors ? "PASS!\n" : "ERROR\n";