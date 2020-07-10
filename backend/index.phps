<?php
/**
 * backend/index.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace urls;

require_once __DIR__ . '/../url-shortner.php';

use \framework\Config;

use \urls\SettingsSchema;
use \urls\Layout;


$settings = new Config(new SettingsSchema);
$settings->fromIniFile(__DIR__ . '/../settings.ini.php');
$config = $settings->get();

new \framework\BackendLayout($config);
