<?php
	header("content-type: text/plain; charset=utf-8");

	require_once __DIR__ . "/../src/classes/HTMLCleaner.php";
	require_once __DIR__ . "/../src/classes/HTMLNodeSettings.php";
	$nodeSettings = new HTMLNodeSettings();
	$html = file_get_contents("text.html");
	$cleaner = new HTMLCleaner($html, $nodeSettings);
	$cleaner->cleanDocument();
	print($cleaner->cleanHTML);
