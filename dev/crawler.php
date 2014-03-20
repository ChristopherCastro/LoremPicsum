<?php
include_once './config.php';
include_once ROOT . 'Lib/Color.php';

if (!file_exists($argv[1])) {
	die("Invalid batch file!\n");
}

require $argv[1];

if (!isset($batch)) {
	die("Invalid batch file, $batch not found!\n");
}

$timeStart = time();
$processedImages = 0;

foreach ($batch as $category => $task_args) {
	if (!isset($task_args['tags']) || empty($task_args['tags'])) {
		echo "Error, invalid task. You must enter at least one tag!\n";
		continue;
	}

	$task_args['tags'] = is_array($task_args['tags']) ? implode(',', $task_args['tags']) : $task_args['tags'];
	$task_args['tags'] = trim(strtolower($task_args['tags']));

	if (!isset($task_args['tag_mode']) || !in_array($task_args['tag_mode'], array('any', 'all'))) {
		$task_args['tag_mode'] = 'any';
	}

	if (!isset($task_args['pages']) || $task_args['pages'] < 0) {
		$task_args['pages'] = 3;
	}

	if (!isset($task_args['page']) || $task_args['page'] < 0) {
		$task_args['page'] = 1;
	}

	if (!isset($task_args['per_page']) || intval($task_args['per_page']) < 0) {
		$task_args['per_page'] = 200;
	}

	if (!isset($task_args['color_extract']) || !is_bool($task_args['color_extract'])) {
		$task_args['color_extract'] = true;
	}

	$validSorts = array(
		'date-posted-asc',
		'date-posted-desc',
		'date-taken-asc',
		'date-taken-desc',
		'interestingness-desc',
		'interestingness-asc',
		'relevance'
	);

	if (!isset($task_args['sort']) || !in_array($task_args['sort'], $validSorts)) {
		$task_args['sort'] = 'relevance';
	}

	echo "-----------------------------------------------------------------\n";
	echo "Begin of task: {$category}\n";
	echo "\ttags: {$task_args['tags']}\n";
	echo "\tpages to fetch: {$task_args['pages']}\n";
	echo "\tstart at page: {$task_args['page']}\n";
	echo "\tphotos per page: {$task_args['per_page']}\n";
	echo "-----------------------------------------------------------------\n";

	$photosPath = PHOTOS_PATH . $category . DS;
	$logger = new KLogger(ROOT . 'logs' . DS, KLogger::DEBUG);
	$logger->logInfo("Category: {$category} | Tags: {$task_args['tags']} | Start from: {$task_args['page']} | Pages: {$task_args['pages']}");

	if (!file_exists($photosPath)) {
		if (!mkdir($photosPath)) {
			$logger->logError("Unable to create photos directory: {$photosPath}");
			continue;
		}
	}
	
	for ($page = 1; $page <= $task_args['pages']; $page++) {
		$Flickr = new Flickr('0ff631b391443a3315297ed0b500f9aa');
		$data = $Flickr->request('photos.search',
			array(
				'tags' => $task_args['tags'],
				'tag_mode' => $task_args['tag_mode'],
				'page' => $task_args['page'],
				'sort' => $task_args['sort'],
				'privacy_filter' => 1,
				'per_page' => $task_args['per_page'],
				'extras' => 'o_dims,tags,url_o,url_l,url_z,url_m,url_q,path_alias'
			)
		);

		if ($task_args['pages'] > $data['photos']['pages']) {
			$task_args['pages'] = $data['photos']['pages'];
		}

		$photoNum = 0;
		$totalPhotos = count($data['photos']['photo']);

		echo "JSON results downloaded, {$data['photos']['total']} images found\n";
		echo "Proccesing page [{$page}/{$task_args['pages']}], {$totalPhotos} photos in this page\n";

		foreach ($data['photos']['photo'] as $photo) {
			/*
			 * Create an empty file named `stop` to safely stop the crawler!
			 * Pressing Crtl+C may interrupt an intermediate step so no rollback possible.
			 **/
			if (file_exists(ROOT . 'stop') && is_file(ROOT . 'stop')) {
				unlink(ROOT . 'stop');
				die("Safe interruption reached!\n");
			}

			$photoNum++;
			$deltaTime = (time() - $timeStart);
			$speed = number_format($processedImages / $deltaTime, 3);

			echo "\n************************************** [{$speed} img/s]\n";
			echo "Image [{$photoNum}/{$totalPhotos}], from page [{$page}/{$task_args['pages']}], category [{$category}]\n\n";

			$search = glob($photosPath . "{$photo['id']}_{$photo['secret']}*.*");

			if (count($search)) {
				echo "\tUps, image already exists! ... skipping\n";
				$logger->logNotice("Ups, image already exists! [cat:{$category}, nm:{$photo['id']}_{$photo['secret']}]");
				continue;
			}

			if (!isset($photo['tags']) || empty($photo['tags'])) {
				echo "\tUps, this photo have not any tag!\n";
				continue;
			}

			$small = array();
			$original = array();

			if (isset($photo['url_m']) && isset($photo['width_m']) && isset($photo['height_m'])) {
				$small = array(
					'source' => $photo['url_m'],
					'width' => $photo['width_m'],
					'height' => $photo['height_m']
				);
			} elseif (isset($photo['url_q']) && isset($photo['width_q']) && isset($photo['height_q'])) {
				$small = array(
					'source' => $photo['url_q'],
					'width' => $photo['width_q'],
					'height' => $photo['height_q']
				);
			}

			if (isset($photo['url_o']) && isset($photo['width_o']) && isset($photo['height_o'])) {
				$original = array(
					'source' => $photo['url_o'],
					'width' => $photo['width_o'],
					'height' => $photo['height_o']
				);
			} elseif (isset($photo['url_l']) && isset($photo['width_l']) && isset($photo['height_l'])) {
				$original = array(
					'source' => $photo['url_l'],
					'width' => $photo['width_l'],
					'height' => $photo['height_l']
				);
			} elseif (isset($photo['url_z']) && isset($photo['width_z']) && isset($photo['height_z'])) {
				$original = array(
					'source' => $photo['url_z'],
					'width' => $photo['width_z'],
					'height' => $photo['height_z']
				);
			}

			if (empty($small) || empty($original)) {
				echo "\tUps, unable to get images sizes! [id:{$photo['id']}] ... skipping\n";
				$logger->LogError("Ups, unable to get images sizes! [id:{$photo['id']} scrt:{$photo['secret']}] ... skipping.");
				continue;
			}

			$smallFileName = basename($small['source']);
			$imageDst = $photosPath . $smallFileName;

			echo "Step 1. Downloading: {$small['source']}\n";
			$imageSource = file_get_contents($small['source']);

			if ($imageSource) {
				echo "\tDone!\n";
			}

			echo "Step 2. Saving at: " . $imageDst . "\n";

			if (file_put_contents($imageDst, $imageSource)) {
				echo "\tDone!\n";

				echo "Step 3. Saving image information\n";
				$insert = $db->Insert(
					'photos',
					array(
						'id' => $photo['id'],
						'owner' => $photo['owner'],
						'secret' => $photo['secret'],
						'server' => $photo['server'],
						'farm' => $photo['farm'],
						'title' => mysql_real_escape_string($photo['title']),
						'source' => mysql_real_escape_string($original['source']),
						'width' => $original['width'],
						'height' => $original['height'],
						'thumbnail' => $smallFileName,
						'category' => $category
					)
				);

				if ($insert !== false) {
					echo "\tDone!\n";
					echo "Step 4. Analyzing image colors\n";

					if ($task_args['color_extract']) {
						$CP = new ColorPalette($imageDst);
						$colors = $CP->extract();
						$colorsExtracted = count(array_keys($colors));
						$theInsert = array('photo_id' => $photo['id']);

						foreach ($colors as $hex => $percent) {
							$theInsert[strtoupper($hex)] = $percent;
						}

						$insert = $db->Insert('photo_colors', $theInsert);

						if ($insert !== false) {
							echo "\t#{$colorsExtracted} colors extracted!. Done!\n";
						} else {
							$error = "## MySQL error(1) (rollback): " . $db->LastError();
							echo "{$error}\n";
							$logger->LogError($error);
							@unlink($imageDst);
							$db->Delete('photos', "id = {$photo['id']}");
							$db->Delete('photo_colors', "photo_id = {$photo['id']}");
							$db->Delete('photo_tags', "photo_id = {$photo['id']}");
							break;
						}
					} else {
						echo "\tSkipped!...\n";
					}

					echo "Step 5. Saving image's tags\n";

					$db->Insert(
						'photo_tags',
						array(
							'photo_id' => $photo['id'],
							'tags' => ' ' . $photo['tags'] . ' ' // `\s` al inicio y al final para usar con `LIKE %...%`
						)
					);

					echo "\tDone!\n";
					$processedImages++;
				} else {
					$error = '## MySQL error(2) (rollback): ' . $db->LastError();
					echo "{$error}\n";
					$logger->LogError($error);
					@unlink($imageDst);
				}
			} else {
				echo "Error, unable to save photo at: {$imageDst}\n";
				$logger->logError("Error, unable to save photo at: {$imageDst}\n");
			}
		}

		echo "Page #{$page} done!\n";
	}
}