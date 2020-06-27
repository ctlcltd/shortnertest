<?php
//SSR

try {
	$config = parse_ini_file(__DIR__ . '/../config.ini.php', true);
} catch (Exception $error) {
	E_NOTICE && die('Missing or wrong configuration file.');

	exit(1);
}
