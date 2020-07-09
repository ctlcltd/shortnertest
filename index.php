<?php
/**
 * index.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

empty($_SERVER['REQUEST_URI']) && exit(1);

try {
	$config = parse_ini_file(__DIR__ . '/settings.ini.php', true);
} catch (Exception $error) {
	E_NOTICE && die('Missing or wrong configuration file.');

	exit(1);
}

try {
	//-TEMP
	$config['Database']['dsn'] = str_replace("../", "./", $config['Database']['dsn']);
	//-TEMP

	$dbh = new PDO(
		$config['Database']['dsn'],
		$config['Database']['username'],
		$config['Database']['password'],
		$config['Database']['options'] === [''] ? NULL : $config['Database']['options']
	);

	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
} catch (PDOException $error) {
	E_NOTICE && die(sprintf('PDO Exception: %s', $error->getMessage()));

	exit(1);
}

$url = $_SERVER['REQUEST_URI'];

//header("location: {$href}", true, 301);
