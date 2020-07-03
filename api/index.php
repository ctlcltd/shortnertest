<?php
/**
 * api/index.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace urls;

require_once __DIR__ . '/../url-shortner.php';

use \urls\API;

ini_set('expose_php', 0);
ini_set('enable_postdata_reading', 1);
ini_set('post_max_size', '128B');
	ini_set('file_uploads', 0);
	ini_set('upload_max_filesize', 0);
	ini_set('max_file_uploads', 0);
ini_set('variables_order', 'GPS');
ini_set('http.request.datashare.cookie', 0);
ini_set('cgi.fix_pathinfo', 1);

new \urls\API;
