<?php
/*
 * SELECT p.*, COUNT(p.color) FROM `photo_colors` AS p WHERE 1 GROUP BY color;
 *
 **/
include_once('./config.php');
include_once(ROOT . 'Lib' . DS . 'ColorPalette.php');

if (isset($_POST['color'])) {
	$hex = preg_replace("/[^0-9a-f]/", '', strtolower($_POST['color']));
	$Color = new Color();
	$Color->fromHex($hex);
	$category = '';
	$palette = ColorPalette::getPalette(false);

	if (isset($_POST['category']) && !empty($_POST['category'])) {
		$category = " AND category = '{$_POST['category']}'";
	}

	$k = $Color->getClosestMatch($palette);

	if ($k !== null) {
		$hex = str_pad(dechex($palette[$k]), 6, "0", STR_PAD_LEFT);
	}	

	$Start = microtime(true);
	$images = $db->Query("
		SELECT * FROM photos
		INNER JOIN photo_colors ON (photo_id = id)
		WHERE 1 = 1 
		{$category}
		AND `{$hex}` > 0
		ORDER BY `{$hex}` DESC
		LIMIT 50"
	);
	echo ((microtime(true) - $Start)) . ' s';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<link rel="stylesheet" type="text/css" href="./css/base.css" />
		<script src="./js/jquery-1.4.3.min.js" type="text/javascript"></script>
		<script type="text/javascript" src="./js/freewall.js"></script>
		<script type="text/javascript" src="./js/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
		<script type="text/javascript" src="./js/fancybox/jquery.easing-1.3.pack.js"></script>
		<script type="text/javascript" src="./js/fancybox/jquery.mousewheel-3.0.4.pack.js"></script>
		<link rel="stylesheet" href="./js/fancybox/jquery.fancybox-1.3.4.css" type="text/css" media="screen" />
		<script>
			$(document).ready(function () {
				if ($("#freewall")) {
					$("#freewall").show();
					$('.cell').each(function (k, v) {
						$(this).width(200 + 200 * Math.random() << 0);
					});

					var wall = new freewall("#freewall");
					wall.reset({
						selector: '.cell',
						animate: true,
						onResize: function() {
							wall.fitWidth();
						}
					});
					wall.fitWidth();
					$(window).trigger('resize');
				}

				$("a[rel=gallery]").fancybox({
					'titleShow' : true,
					'titlePosition' : 'over',
					'transitionIn' : 'elastic',
					'transitionOut' : 'elastic',
					'easingIn' : 'easeOutBack',
					'easingOut' : 'easeInBack',
					'titleFormat' : function(title, currentArray, currentIndex, currentOpts) {
						return '<span id="fancybox-title-over">[Image ' +  (currentIndex + 1) + '/' + currentArray.length + '] ' + title + '</span>';
					}					
				});
			});

			function selectColor(hex) {
				$('#color').val(hex);
				$('#search').submit();
			}
		</script>
	</head>

	<body>
		<div class="palette">
			<div>
				<form action="" method="post" id="search">
					<input type="hidden" name="color" id="color" />
					<label>Category:</label>
					<select name="category" id="category">
						<option value="" <?php echo !isset($_POST['category']) ||  empty($_POST['category']) ? 'selected' : ''; ?>>Any</option>
						<option value="nature" <?php echo isset($_POST['category']) && $_POST['category'] == 'nature' ? 'selected' : ''; ?>>Nature</option>
						<option value="business" <?php echo isset($_POST['category']) && $_POST['category'] == 'business' ? 'selected' : ''; ?>>Business</option>
						<option value="cats" <?php echo isset($_POST['category']) && $_POST['category'] == 'cats' ? 'selected' : ''; ?>>Cats</option>
						<option value="abstract" <?php echo isset($_POST['category']) && $_POST['category'] == 'abstract' ? 'selected' : ''; ?>>Abstract</option>
						<option value="internet" <?php echo isset($_POST['category']) && $_POST['category'] == 'internet' ? 'selected' : ''; ?>>Internet</option>
						<option value="games" <?php echo isset($_POST['category']) && $_POST['category'] == 'games' ? 'selected' : ''; ?>>Games</option>
						<option value="architecture" <?php echo isset($_POST['category']) && $_POST['category'] == 'architecture' ? 'selected' : ''; ?>>Architecture</option>
						<option value="people" <?php echo isset($_POST['category']) && $_POST['category'] == 'people' ? 'selected' : ''; ?>>People</option>
						<option value="animals" <?php echo isset($_POST['category']) && $_POST['category'] == 'animals' ? 'selected' : ''; ?>>Animals</option>
						<option value="sports" <?php echo isset($_POST['category']) && $_POST['category'] == 'sports' ? 'selected' : ''; ?>>Sports</option>
						<option value="movies" <?php echo isset($_POST['category']) && $_POST['category'] == 'movies' ? 'selected' : ''; ?>>Movies</option>
						<option value="fashion" <?php echo isset($_POST['category']) && $_POST['category'] == 'fashion' ? 'selected' : ''; ?>>Fashion</option>
						<option value="food" <?php echo isset($_POST['category']) && $_POST['category'] == 'food' ? 'selected' : ''; ?>>Food</option>
					</select>
				</form>
			</div>

			<div>
				<?php foreach (ColorPalette::getPalette() as $hex): ?>
				<a href="" class="cp" title="#<?php echo $hex; ?>" style="background:#<?php echo $hex; ?>;" onclick="selectColor('<?php echo $hex; ?>'); return false;"></a>
				<?php endforeach; ?>
			</div>
		</div>

		<?php if (isset($images)): ?>
			<div class="chosen-color" style="background:#<?php echo $_POST['color']; ?>;"></div>

			<div id="freewall" style="display:none;">
				<?php foreach ($images as $image): ?>
					<?php list($width, $height) = getimagesize(ROOT . 'photos' . DS . $image['category'] . DS . $image['thumbnail']); ?>
					<a
						class="cell"
						rel="gallery"
						title="<?php echo $image['title']; ?>"
						href="<?php echo $image['source']; ?>"
						style="width:<?php echo $width; ?>px; height:<?php echo $height; ?>px; background:url(./photos/<?php echo $image['category']; ?>/<?php echo $image['thumbnail']; ?>) no-repeat #<?php echo $_POST['color']; ?>; background-size:cover; background-position:center;"></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		
	</body>
</html>