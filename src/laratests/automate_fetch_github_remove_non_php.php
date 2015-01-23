#!/usr/bin/env php
<?php
// future proving on styling change
// retrieve from github, copied to target(s) folder, execute removeNonePHP, then work on differences manually

/*
 * the main function to call, fetch()
 */
fetch();

function fetch() {
	$targets = [
		"https://github.com/laravel/laravel/archive/master.zip",
		"https://github.com/laravel/framework/archive/4.2.zip",
	];
	foreach ($targets as $target) {
		preg_match("/\/([^\/]+)\/archive\/([^\/]+)\.zip$/", $target, $matches); // print_r($matches);
		array_shift($matches);
		$folder = implode('-', $matches);
		$filename = $folder . '.zip'; // echo $filename;
		try {
			file_put_contents($filename, @file($target));
			// file_put_contents($filename, @file($filename));
			$zip = new ZipArchive;
			$res = $zip->open($filename);
			if ($res === TRUE) {
				$zip->extractTo('./');
				$zip->close();
			}
		} catch (Exception $e) {
			echo $e;
		}
		echo "Downloaded $target, extracted to $folder", PHP_EOL;
		echo removeNonePHP($folder, '');
		echo recursiveCopy($folder, $folder . "_phpfmt");
		echo recursiveCopy($folder, $folder . "_kdiff3");
	}
	return true;
}

function recursiveCopy($target, $destination) {
	$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($target));

	$it->rewind(); // create directories part
	while ($it->valid()) {
		if ($it->isDir()) {
			$path = str_replace($target, $destination, $it->getPath());
			if (!file_exists($path)) {
				mkdir($path);
			}
			// echo "Created directory: ", $path, PHP_EOL;
		}
		$it->next();
	}

	$it->rewind(); // copy files part
	while ($it->valid()) {
		if (!$it->isDot()) {
			$src = $it->getPathName();
			$dest = str_replace($target, $destination, $src);
			copy($src, $dest);
			// echo "Copied file: " . $src . " => " . $dest . "\n";			// die;
		}
		$it->next();
	}

	return "Duplicated $target to $destination\n";
}

function removeNonePHP($target, $appended) {
	$directory = $target . $appended . DIRECTORY_SEPARATOR; // echo $directory;
	$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

	$it->rewind(); // only php part
	while ($it->valid()) {
		if (!$it->isDot()) {
			if (!preg_match('/\.php$/', $it->key())) {
				unlink($it->key());
				// echo "deleted file: ", $it->key(), PHP_EOL;
			}
		}
		$it->next();
	}

	$it->rewind(); // remove empty folder part
	while ($it->valid()) {
		if ($it->isDir() and preg_match('/\.\.$/', $it->key())) {
			if (count(glob($it->getPath() . DIRECTORY_SEPARATOR . "*")) === 0) {
				rmdir($it->getPath());
				// echo "removed directory: ", $it->key(), PHP_EOL;
			}
		}
		$it->next();
	}
	return "Cleaned $directory\n";
}
