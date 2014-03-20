<?php
include_once('./config.php');
include_once('./Lib/ColorPalette.php');

$colors = $db->Query('SELECT photo_id, hex, amount FROM photo_colors');
$i = 0;
$totalRows = count($colors);

echo "Updating {$totalRows} colors...\n";

foreach ($colors as $color) {
	$i++;
	$q = "SELECT amount FROM photo_colors WHERE photo_id = {$color['photo_id']}";
	$amountCol = $db->QueryOneColumn('amount', $q);
	$total = array_sum($amountCol);

	if ($total > 0) {
		$percent = ($color['amount'] * 100) / $total;
	} else {
		echo "Total = 0, p:{$color['photo_id']} h:{$color['hex']} -> (" . implode(',', $amountCol) . ")\n";
		$percent = 0;
	}

	$palette = ColorPalette::getPalette(false);
	$C = new Color();
	$C->fromHex($color['hex']);
	$k = $C->getClosestMatch($palette);

	if ($k !== null) {
		$color['hex'] = strtoupper(str_pad(dechex($palette[$k]), 6, "0", STR_PAD_LEFT));
		$C->fromHex($color['hex']);
	}

	$RGB = $C->toRgbInt();
	$LAB = $C->toLabCie();
	$HSV = $C->toHsvFloat();

	$db->Update('photo_colors',
		array(
			'hex' => strtoupper($color['hex']),
			'percent' => $percent,
			'red' => $RGB['red'],
			'green' => $RGB['green'],
			'blue' => $RGB['blue'],
			'l' => $LAB['l'],
			'a' => $LAB['a'],
			'b' => $LAB['b'],
			'hue' => $HSV['hue'],
			'sat' => $HSV['sat'],
			'val' => $HSV['val']
		),
		"photo_id = {$color['photo_id']} AND hex = '{$color['hex']}'"
	);

	echo "[{$i}/{$totalRows}] p:{$color['photo_id']} h:{$color['hex']} DONE!\n";
}