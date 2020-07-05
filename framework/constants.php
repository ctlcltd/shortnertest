<?php
/**
 * framework/constants.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace framework;

const VALUE_NULL = 0;
const VALUE_INT = 1;
const VALUE_STR = 2;
const VALUE_BOOL = 5;
const VALUE_ARR = 6;


const CONFIG = [
	'Host' => [
		'ssr' => true,
		'error_404' => '',
		'error_50x' => '',
		// abs path
		'backend_path' => './backend',
	],
	'Network' => [
		'setup' => false,
		'api_test' => true
	]
];

const ROUTES = [
	'/' => [
		'GET' => [ 'call' => 'exploiting', 'auth' => false ],
		'POST' => [ 'access' => true ]
	],
	'/setup' => [
		'GET' => [ 'call' => 'install', 'setup' => true ]
	],
	'/sample' => [
		'GET' => [ 'call' => 'sample' ]
	]
];
