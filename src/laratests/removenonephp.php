#!/usr/bin/env php
<?php

// removeNonePHP('laravel-master', '_phpfmt');
// removeNonePHP('laravel-master', '_new');
// removeNonePHP('laravel-master', '');
removeNonePHP('framework-4.2', '_phpfmt');
removeNonePHP('framework-4.2', '_new');
removeNonePHP('framework-4.2', '');
function removeNonePHP($target, $appended) {

	$directory = $target . $appended . DIRECTORY_SEPARATOR;
	// echo $directory;
	$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
	$it->rewind();
	while ($it->valid()) {
		if (!$it->isDot()) {
			if (preg_match('/\.php$/', $it->key())) {
				// echo $it->key(), PHP_EOL;
				// die;
			} else {
				unlink($it->key());
			}
		}
		$it->next();
	}
	return true;
}
