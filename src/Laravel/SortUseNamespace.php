<?php
class SortUseNamespace extends AdditionalPass {
	public function candidate($source, $foundTokens) {
		if (isset($foundTokens[T_USE])) {
			return true;
		}
		return false;
	}

	public function format($source) {
		$digFromHere = $this->tokensInLine($source);
		// $this->understand($source);
		$seenUseToken = [];
		foreach ($digFromHere as $index => $line) {
			if (preg_match('/^(?:T_WHITESPACE )?(T_USE) T_WHITESPACE /', $line, $match)) {
				array_push($seenUseToken, $index);
			}
		}
		// print_r($seenUseToken);
		$source = $this->sortTokenBlocks($seenUseToken, $source);
		return $source;
	}

	private function sortTokenBlocks($seenArray, $source) {
		$lines = explode("\n", $source);
		$buckets = $this->getTokensBuckets($seenArray);
		// print_r($buckets);

		foreach ($buckets as $bucket) {
			$start = $bucket[0];
			$stop = $bucket[(count($bucket) - 1)];

			$t_use = array_splice($lines, $start, ($stop - $start + 1));
			$t_use = $this->sortByLength($t_use);

			$head = array_splice($lines, 0, $start);
			$lines = array_merge($head, $t_use, $lines);
		}
		return implode("\n", $lines); //$source;
	}

	private function getTokensBuckets($seenArray) {
		$temp = [];
		$seenBuckets = [];
		foreach ($seenArray as $j => $index) {
			// echo "$j => $index ";
			if (0 !== $j) {
				if (($index - 1) !== $seenArray[($j - 1)]) {
					// echo "diff with previous ";
					if (count($temp) > 1) {
						array_push($seenBuckets, $temp); //push to bucket
						// echo "pushed ";
					}
					$temp = []; // clear temp
				}
			}
			array_push($temp, $index);
			if ((count($seenArray) - 1) == $j and (count($temp) > 1)) {
				// echo "reached end ";
				array_push($seenBuckets, $temp); //push to bucket
			}
			// echo PHP_EOL;
		}
		return $seenBuckets;
	}

	private function sortByLength($inArray) {
		$outArray = [];
		// prepend strlen in front, then sort, then remove prepend, done.
		foreach ($inArray as $line) {
			$prepend = strlen($line) . " $line"; // use ' ' + 'use' as delimit later on
			array_push($outArray, $prepend);
		}
		sort($outArray);
		$cleaned = [];
		foreach ($outArray as $line) {
			$unprepend = preg_replace('/^\d+ /', '', $line);
			array_push($cleaned, $unprepend);
		}
		return $cleaned;
	}

	private function understand($source) {
		print_r(explode("\n", $source));
		echo "===================================\n";
		print_r($this->tokensInLine($source));
	}

	private function tokensInLine($source) {
		$tokens = token_get_all($source);
		$processed = [];
		$seen = 1; // token_get_all always starts with 1
		$tokensLine = '';
		foreach ($tokens as $index => $token) {
			if (isset($token[2])) {
				$currLine = $token[2];
				if ($seen != $currLine) {
					$processed[($seen - 1)] = $tokensLine;
					// $tokensLine = token_name($token[0]) . "($index) ";
					$tokensLine = token_name($token[0]) . " ";
					$seen = $currLine;
				} else {
					// $tokensLine .= token_name($token[0]) . "($index) ";
					$tokensLine .= token_name($token[0]) . " ";
					// echo ($tokensLine);die;
				}
			} else {
				// $tokensLine .= $token . "($index) ";
				$tokensLine .= $token . " ";
			}
		}
		$processed[($seen - 1)] = $tokensLine; // consider the last line
		return $processed;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getDescription() {
		return 'Simple sorting of T_USE to follow Laravel Framework';
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getExample() {
		return <<<'EOT'
<?php

namespace Illuminate\Foundation;

use Closure;
use Illuminate\Config\FileEnvironmentVariablesLoader;
use Illuminate\Config\FileLoader;
use Illuminate\Container\Container;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Exception\ExceptionServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\RoutingServiceProvider;
use Illuminate\Support\Contracts\ResponsePreparerInterface;
use Illuminate\Support\Facades\Facade;
use Stack\Builder;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
?>
to
<?php namespace Illuminate\Foundation;

use Closure;
use Stack\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Config\FileLoader;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Routing\RoutingServiceProvider;
use Illuminate\Exception\ExceptionServiceProvider;
use Illuminate\Config\FileEnvironmentVariablesLoader;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Illuminate\Support\Contracts\ResponsePreparerInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
?>
EOT;
	}
}
