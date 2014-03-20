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
		0xffffff,
		0xf2eac6,
		0xf8e275,
		0xfa9a4d,
		0xf9a986,
		0xfaaca8,
		0xfc6d99,
		0xff0000,
		0xac2424,
		0xa746b1,
		0xc791f1,
		0xa4a6fd,
		0x1d329d,
		0x2ccacd,
		0x9cd8f4,
		0x62854f,
		0xa9cb6c,
		0xcab775,
		0x815b10,
		0x777777,
		0x000000
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
 * Returns the most dominants colors from the given image and its percent.
 *
 * # Output example:
 *
 *     array(
 *         'FF00AA' => 91,
 *         '885418' => 80
 *         ...
 *     )
 *
 * @param intener $numColors The number of colors to extract.
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
		if (count(ColorPalette::$__closestMatchCache) > 500) {
			ColorPalette::$__closestMatchCache = array();
		}

		$total = array_sum(array_values($colors));
		$_colors = array();

		foreach ($colors as $hex => $amount) {
			$percent = round(($amount * 100) / $total);

			if ($percent > 5) {
				$_colors[$hex] = $percent;
			}
		}

		return $_colors; 
	}
} 