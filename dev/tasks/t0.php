<?php
//fashion, film, sports, animals
$batch = array(
	'internet' => array(
		'tags' => array('internet', 'web'),
		'tag_mode' => 'any', // Either 'any' for an OR combination of tags, or 'all' for an AND combination. Defaults to 'any' if not specified.
		'pages' => 11,
		'page' => 1,
		'per_page' => 500,
		'color_extract' => false
	),
	'games' => array(
		'tags' => array('games', 'video game', 'playstation', 'xbox', 'nintendo', 'super mario', 'gameboy', 'tetris'),
		'tag_mode' => 'any',
		'pages' => 11,
		'page' => 1,
		'per_page' => 500,
		'color_extract' => false
	),
	'architecture' => array(
		'tags' => array('architecture'),
		'tag_mode' => 'any',
		'pages' => 11,
		'page' => 6,
		'per_page' => 500,
		'color_extract' => false
	)
);