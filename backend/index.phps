<?php
//SSR

libxml_use_internal_errors(true);

$tpl = new \DOMDocument;
$tpl->loadHTMLFile(__DIR__ . '/index.html');

echo $tpl->saveHTML();
