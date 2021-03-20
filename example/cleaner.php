<?php
	header("content-type: text/plain; charset=utf-8");

	require_once __DIR__ . "/../src/classes/HTMLCleaner.php";
	$html = file_get_contents("text.html");
	$cleaner = new HTMLCleaner($html);
	$cleaner->cleanDocument();
	print($cleaner->cleanHTML);
