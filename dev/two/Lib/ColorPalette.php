<?php
include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Color.php');

/**
 * Color Palette class.
 *
 * Extracts dominants colors of an image based on a given color palette.
 * (It will return the best fit to the given palette)
 * Supports JPG, GIF and PNG. And RGB, LAB and HSV color spaces.
 *
 * @dependencies Color.
 * @author Christopher Castro <chris@quickapps.es>
 */
class ColorPalette extends Color {
	private $__imageFile = null;
	private static $__closestMatchCache = array();
	private static $__palette = array(
		0xfddce5, 0xfadcec, 0xf6daed, 0xeedced, 0xe4daee, 0xdfe1f1, 0xdfeefa, 0xe1f5fa, 0xe1f3f0, 0xe2f1de, 0xeaf3d9, 0xf8f9dc, 0xfefcdf, 0xfff1dc, 0xfee1dc, 0xfcd4d7,
		0xfac7d2, 0xf5c7da, 0xedc7dc, 0xdfc7df, 0xd0c4e3, 0xcbcbe7, 0xc9e1f6, 0xcdecf5, 0xceeae5, 0xcee6c9, 0xd7eac3, 0xf0f3c7, 0xfcf6c9, 0xffe7c9, 0xfdcec7, 0xf9b8be,
		0xf5a3b6, 0xe7a6c1, 0xd3a7c4, 0xbfa5c4, 0xada3c7, 0xa7aad0, 0xa9c9ed, 0xaed7ea, 0xafd6d0, 0xadd1aa, 0xbed69f, 0xdfe8a4, 0xfcf1a6, 0xffcfa5, 0xf9aca5, 0xf397a0,
		0xee839b, 0xd689ae, 0xbf8bb2, 0xa387b2, 0x8a82b2, 0x848bba, 0x8ab7e1, 0x92cde1, 0x95c9be, 0x95c18d, 0xa9c97f, 0xd0da83, 0xf9ea87, 0xfebd84, 0xf38d84, 0xee7b82,
		0xea6a81, 0xce709e, 0xb176a2, 0x8c6ea1, 0x716da1, 0x6b73a7, 0x73a1d2, 0x7fc1d9, 0x83bfb4, 0x83ba72, 0x9abe5e, 0xc3d268, 0xf8e36c, 0xfcaa6b, 0xee796b, 0xeb626d,
		0xe84b6e, 0xcb6092, 0xa5689b, 0x81659b, 0x67669d, 0x596a9f, 0x638fc3, 0x73bdd5, 0x7dbca9, 0x7cb45e, 0x8aba49, 0xbccd40, 0xf6da46, 0xf99c47, 0xec6449, 0xe94e56,
		0xe73863, 0xcb4e8d, 0xa26095, 0x7e6199, 0x5d629b, 0x4b669e, 0x5486bd, 0x6cbdd4, 0x75baa1, 0x73b15a, 0x80b74c, 0xb4c93d, 0xf6d727, 0xf68e2b, 0xe84b33, 0xe73843,
		0xe72653, 0xcd498c, 0xa05e92, 0x7b5f98, 0x53609a, 0x46639c, 0x4e80b8, 0x6abbd3, 0x71b99e, 0x6fb058, 0x7eb44d, 0xadc43f, 0xf1d41c, 0xf18828, 0xe7402d, 0xde323c,
		0xd6254b, 0xc24188, 0x9d5990, 0x765a95, 0x4e5d98, 0x445e9a, 0x437eb1, 0x55b4c7, 0x69b498, 0x68ac59, 0x70af50, 0x9fc043, 0xd5c427, 0xd9802d, 0xd73b2e, 0xc83239,
		0xba2944, 0xb13381, 0x914c8e, 0x6f4d8f, 0x494e8f, 0x405191, 0x3071a6, 0x29a5b4, 0x44ab8c, 0x48a85b, 0x61aa54, 0x8bb449, 0xb7ac3e, 0xb97438, 0xb93530, 0xa72d36,
		0x95253c, 0x912a76, 0x863c89, 0x693e88, 0x403f85, 0x3a4286, 0x19668f, 0x008c92, 0x00957d, 0x0c9556, 0x429553, 0x77974b, 0x908d41, 0x956830, 0x952b29, 0x87212a,
		0x79172a, 0x792464, 0x6e2e76, 0x513074, 0x382e71, 0x303870, 0x214e77, 0x017079, 0x007a67, 0x107b48, 0x3b7b45, 0x657b42, 0x77733d, 0x795025, 0x77221d, 0x651c21,
		0x541724, 0x591044, 0x511f59, 0x3c1d59, 0x231d57, 0x212257, 0x0e3b59, 0x01535a, 0x015c45, 0x205c31, 0x335c2d, 0x475c2b, 0x5a5527, 0x593d1c, 0x541f0e, 0x431716,
		0x33101d, 0x331728, 0x2e1435, 0x241033, 0x1c1733, 0x101933, 0x082335, 0x023035, 0x033829, 0x1a3921, 0x233a21, 0x2c3b20, 0x3a351f, 0x382510, 0x33140c, 0x230b09,
		0x140306, 0x14040c, 0x140817, 0x0c0614, 0x080614, 0x060814, 0x040a14, 0x031014, 0x03140e, 0x081a0c, 0x0e1c0a, 0x141c0a, 0x191909, 0x170e06, 0x140604, 0x140604,
		0xffffff, 0xeeeeee, 0xdddddd, 0xcccccc, 0xbbbbbb, 0xaaaaaa, 0x999999, 0x888888, 0x777777, 0x666666, 0x555555, 0x444444, 0x333333, 0x222222, 0x111111, 0x000000
	);

/**
 * Class constructor.
 *
 * @param string $imageFile Path to image file.
 * @return void
 */	
	public function __construct($imageFile) {
		if (!file_exists($imageFile)) {
			user_error('Image file not found!');
			return;
		}

		$this->__imageFile = $imageFile;
	}

/**
 * Returns the palette array.
 *
 * @param boolean $hex TRUE returns palette as string-hex values (ex. 'ff00ee').
 * FALSE returns int values (ex. 0xff00ee).
 * @return array List of colors
 */
	public static function getPalette($hex = true) {
		$palette = ColorPalette::$__palette;

		if ($hex) {
			foreach ($palette as $k => $v) {
				$palette[$k] = strtoupper(str_pad(dechex($v), 6, "0", STR_PAD_LEFT));
			}
		}

		return $palette;
	}

/**
 * Overwrites the actual color palette.
 *
 * @param array $palette Array of colors as INTEGER values (ex. 0x007a67)
 * @return boolean TRUE on success, FALSE otherwise.
 */
	public static function setPalette($palette) {
		foreach ($palette as $color) {
			if (!is_integer($color)) {
				return false;
			}
		}

		ColorPalette::$__palette = $palette;

		return true;
	}

/**
 * Returns the most dominants colors from the given image.
 *
 * # Output example:
 *
 *     array(
 *         'FF00AA' => array(
 *             'amount' => 200,
 *             'percent' => 0.63
 *          ),
 *         'CC0355' => array(
 *         ...
 *     )
 *
 * - amount: Number of pixels matching this color (based on all image's colors).
 * - percent: Percent [0, 1] of pixels matching this colors (based on palette).
 *
 * @return mixed Associative array of colors on success. Or boolean FALSE on error.
 */
	public function extract() {
		$imageFile = $this->__imageFile;
		$colors = array(); 
		list($width, $height, $type) = @getimagesize($imageFile); 

		if (!$width) { 
			user_error('Unable to get image information'); 
			return false; 
		}

		switch ($type) {
			case IMAGETYPE_PNG:
				$img = @imagecreatefrompng($imageFile);
			break;

			case IMAGETYPE_GIF:
				$img = @imagecreatefromgif($imageFile);
			break;

			default:
				case IMAGETYPE_JPEG:
					$img = @imagecreatefromjpeg($imageFile);
			break;
		}

		if (!$img) { 
			user_error('Unable to open image file'); 
			return false; 
		}

		for ($x = 0; $x < $width; $x += 4) {
			for ($y = 0; $y < $height; $y += 4) {
				$pixelColor = imagecolorat($img, $x, $y);
				$rgb = imagecolorsforindex($img, $pixelColor);

				$red = round(round(($rgb['red'] / 0x33)) * 0x33);
				$green = round(round(($rgb['green'] / 0x33)) * 0x33);
				$blue = round(round(($rgb['blue'] / 0x33)) * 0x33);
				$HEX = sprintf('%02X%02X%02X', $red, $green, $blue);

				if (strlen($HEX) != 6) {
					continue;
				}

				if (!isset(ColorPalette::$__closestMatchCache[$HEX])) {
					$this->fromHex($HEX);
					$k = $this->getClosestMatch(ColorPalette::$__palette);

					if ($k !== null) {
						ColorPalette::$__closestMatchCache[$HEX] = str_pad(dechex(ColorPalette::$__palette[$k]), 6, "0", STR_PAD_LEFT);
						$HEX = ColorPalette::$__closestMatchCache[$HEX];
					}
				} else {
					$HEX = ColorPalette::$__closestMatchCache[$HEX];
				}

				$HEX = strtoupper($HEX);

				if (array_key_exists($HEX, $colors)) { 
					$colors[$HEX]++; 
				} else { 
					$colors[$HEX] = 1; 
				} 
			} 
		}

		/**
		 * Flush cache if it's too big.
		 *
		 */
		if (count(ColorPalette::$__closestMatchCache) > 800) {
			ColorPalette::$__closestMatchCache = array();
		}

		$total = array_sum(array_values($colors));
		$palette = ColorPalette::getPalette();
		$out = array();
		
		foreach ($palette as $hex) {
			$out[$hex] = array(
				'amount' => 0,
				'percent' => 0
			);

			if (isset($colors[$hex])) {
				$out[$hex] = array(
					'amount' => $colors[$hex],
					'percent' => ($colors[$hex] / $total)
				);
			}
		}

		return $out; 
	}
} 
