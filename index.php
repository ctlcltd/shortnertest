<?php
empty($_SERVER['REQUEST_URI']) && exit(1);

try {
	$config = parse_ini_file(__DIR__ . '/config.ini.php', true);
} catch (Exception $error) {
	E_NOTICE && die('Missing or wrong configuration file.');

	exit(1);
}

try {
	//-TEMP
	$config['Database']['dbdsn'] = str_replace("../", "./", $config['Database']['dbdsn']);
	//-TEMP

	$dbh = new PDO(
		$config['Database']['dbdsn'],
		$config['Database']['dbuser'],
		$config['Database']['dbpass'],
		$config['Database']['dbopts']
	);

	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
} catch (PDOException $error) {
	E_NOTICE && die(sprintf('PDO Exception: %s', $error->getMessage()));

	exit(1);
} finally {
	$url = $_SERVER['REQUEST_URI'];


	//header("location: {$href}", true, 301);
}
