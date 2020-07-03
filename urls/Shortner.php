<?php
/**
 * urls/Shortner.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace urls;

use \stdClass;


interface ShortnerInterface {
	public static function shortner(string $src_url);
	public static function resolver(string $uri);
}

class Shortner implements ShortnerInterface {
	public static function shortner(string $src_url) {
		$src_url = parse_url($src_url);
		$src_url = substr($src_url['path'], 1) .
			(isset($src_url['query']) ? '?' . $src_url['query'] : '') .
			(isset($src_url['fragment']) ? '#' . $src_url['fragment'] : '');

		// catch @ Error
		$index = @zlib_encode($src_url, ZLIB_ENCODING_DEFLATE, 9);
		$index = base64_encode($index);

		$data = new stdClass;
		$data->index = $index;
		$data->slug = hash('adler32', $index);

		return $data;
	}

	public static function resolver(string $uri) {
		$uri = substr($uri, 1);

		// catch @ Error
		$index = @zlib_decode($uri, ZLIB_ENCODING_DEFLATE, 9);
		$index = base64_encode($uri);

		return $index;
	}
}
