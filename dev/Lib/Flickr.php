<?php
class Flickr {
	private $api;

	public function __construct($api) {
		$this->api = $api;
	}

	public function request($method, $args = array()) {
		if (substr($method, 0, 7) != 'flickr.') {
			$method = "flickr." . $method;
		}

		foreach ($args as $key => $value) {
			$args[$key] = $key . '=' . urlencode($value);
		}

		$args = implode('&', $args);
		$url = "http://api.flickr.com/services/rest/?format=php_serial&method={$method}&api_key={$this->api}&{$args}";
		$response = file_get_contents($url);

		return unserialize($response);
	}
	
	public function getImageSizes($id) {
		$photoInfo = $this->request('photos.getSizes', array('photo_id' => $id));
		$sizes = array();

		if (isset($photoInfo['sizes']['size'])) {
			foreach ($photoInfo['sizes']['size'] as $size) {
				$sizes[$size['label']] = $size;
			}

			return $sizes;
		}

		return false;
	}

	public function getImageTags($id) {
		$tags = $this->request('tags.getListPhoto', array('photo_id' => $id));

		if (isset($tags['photo']['tags']['tag'])) {
			return $tags['photo']['tags']['tag'];
		}

		return false;
	}
}