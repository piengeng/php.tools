#!/usr/bin/env php
<?php
# Copyright (c) 2014, Carlos C
# All rights reserved.
#
# Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
#
# 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
#
# 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
#
# 3. Neither the name of the copyright holder nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.
#
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
$isHHVM = (false !== strpos(phpversion(), 'hhvm'));
$shortTagEnabled = ini_get('short_open_tag');
$opt = getopt('v', ['verbose', 'deployed', 'coverage', 'coveralls', 'testNumber:', 'stop']);
$isCoverage = isset($opt['coverage']) || isset($opt['coveralls']);
$isCoveralls = isset($opt['coveralls']);
if ($isCoverage) {
	require 'vendor/autoload.php';
	$filter = new PHP_CodeCoverage_Filter();
	$filter->addFileToBlacklist("fmt.php");
	$filter->addFileToBlacklist("fmt.src.php");
	$filter->addFileToBlacklist("test.php");
	$filter->addDirectoryToBlacklist("vendor");
	$coverage = new PHP_CodeCoverage(null, $filter);
}

$testNumber = "";
if (isset($opt['testNumber'])) {
	$testNumber = sprintf("%03d", (int) $opt['testNumber']);
}
$start = microtime(true);
$testEnv = true;
ob_start();
if (!isset($opt['deployed'])) {
	include realpath(__DIR__ . "/fmt.src.php");
} else {
	include realpath(__DIR__ . "/../fmt.php");
}
ob_end_clean();

// ---------------------------------------------------------------------------------------------------------------
echo 'LaravelStyle further tests...', PHP_EOL;

// phpfmtIt('laratests', 'laravel-master', '_phpfmt'); // use sparingly
// phpfmtIt('laratests', 'framework-4.2', '_phpfmt'); // use sparingly
require 'Laravel/LaravelStyleNew.php';
require 'Laravel/NoSpaceBetweenFunctionAndBracket.php';
require 'Laravel/SpaceAroundExclaimationMark.php';
require 'Laravel/NoneDocBlockMinorCleanUp.php';
require 'Laravel/SortUseNamespace.php';
require 'Laravel/AlignEqualsByConsecutiveBlocks.php';
function phpfmtIt($container, $target, $appended) {
	$fmt = new CodeFormatter();
	$fmt->addPass(new TwoCommandsInSameLine());
	$fmt->addPass(new RemoveIncludeParentheses());
	$fmt->addPass(new NormalizeIsNotEquals());
	$fmt->addPass(new OrderUseClauses());
	$fmt->addPass(new AddMissingCurlyBraces());
	$fmt->addPass(new ExtraCommaInArray());
	$fmt->addPass(new NormalizeLnAndLtrimLines());
	$fmt->addPass(new MergeParenCloseWithCurlyOpen());
	$fmt->addPass(new MergeCurlyCloseAndDoWhile());
	$fmt->addPass(new MergeDoubleArrowAndArray());
	$fmt->addPass(new ResizeSpaces());
	$fmt->addPass(new ReindentColonBlocks());
	$fmt->addPass(new ReindentLoopColonBlocks());
	$fmt->addPass(new ReindentIfColonBlocks());
	// $fmt->addPass(new AlignEquals());
	// $fmt->addPass(new AlignDoubleArrow());
	$fmt->addPass(new ReindentObjOps());
	$fmt->addPass(new Reindent());
	$fmt->addPass(new EliminateDuplicatedEmptyLines());
	$fmt->addPass(new LeftAlignComment());
	$fmt->addPass(new RTrim());

	if ($appended == '_new') {
		$fmt->addPass(new SmartLnAfterCurlyOpen());
		$fmt->addPass(new LaravelStyleNew());
	} else {
		$fmt->addPass(new SmartLnAfterCurlyOpen());
		$fmt->addPass(new LaravelStyle());
	}

	$directory = $container . DIRECTORY_SEPARATOR . $target;
	$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
	$it->rewind();
	while ($it->valid()) {
		if (!$it->isDot()) {
			if (preg_match('/\.php$/', $it->key())) {
				$content = file_get_contents($it->key());
				$got = $fmt->formatCode($content);
				$saveTo = $directory . $appended . DIRECTORY_SEPARATOR . $it->getSubPathName();
				echo "Worked on " . $saveTo . "\n";
				file_put_contents($saveTo, $got);
				// echo $saveTo, PHP_EOL;				// die;
			}
		}
		$it->next();
	}
	return true;
}

// laratests\laravel-master\app\controllers\BaseController.php ! -> ' ! '
// $ls = new LaravelStyleNew();
// $source = file_get_contents('laratests\laravel-master_phpfmt\app\controllers\BaseController.php');
// file_put_contents('laratests\laravel-master_new\app\controllers\BaseController.php', $ls->format($source));

// laratests\laravel-master\app\config\compile.php
// $ls = new LaravelStyleNew();
// $source = file_get_contents('laratests\laravel-master_phpfmt\app\config\compile.php');
// file_put_contents('laratests\laravel-master_new\app\config\compile.php', $ls->format($source));

// laratests\laravel-master\app\filters.php function () and AllmanBracket

// phpfmtIt('laratests', 'laravel-master', '_phpfmt');
// phpfmtIt('laratests', 'laravel-master', '_new');

// laratests\laravel-master\app\models\User.php Sort use
// $ls = new LaravelStyleNew();
// $source = file_get_contents('laratests\laravel-master_phpfmt\app\models\User.php');
// file_put_contents('laratests\laravel-master_new\app\models\User.php', $ls->format($source));

// laratests\laravel-master\app\start\global.php
$qk = new CodeFormatter();
$qk->addPass(new SmartLnAfterCurlyOpen());
$qk->addPass(new LaravelStyle());
$source = file_get_contents('laratests\spacearoundyell_before.php');
file_put_contents('laratests\spacearoundyell_after.php', $qk->formatCode($source));
//
//
// ---------------------------------------------------------------------------------------------------------------
echo "Took ", (microtime(true) - $start), PHP_EOL;
exit(0);
