<?php
class AllmanStyleBracesByPienGeng extends FormatterPass {
	public function candidate($source, $foundTokens) {
		return true;
	}

/*
allman redo

mark all {}
ignore if valid paired on single line, do nothing
base on mark all, determine each paired level (how many indentation)

the rest of text between valid pairs.. erm, reindent? follow level..

consider inline html as well. to avoid stupidity of laravel/non-laravel user.

use tokensInLine() if the line is non other than comment or inline html, ignore, array ignore.

 */

	public function format($source) {
		// echo strlen((string) count($lines));
		// $padlen = strlen((string) count($lines));
		// echo str_pad('i', $padlen, ' ', STR_PAD_LEFT) . " : o c l\n";
		// foreach ($this->tokensInLine($source) as $index => $line) {
		// 		echo str_pad($index, $padlen, ' ', STR_PAD_LEFT) . " : $open $close $level $lines[$index] \n";
		// 		// if ( !== substr_count($line, '}')) {
		// 		// 	echo "needs $index $line\n";
		// 		// }
		$source = $this->curliesBreaker($source); // first pass, expand curlies token, ensure each curies are on its own line

		// level generator, then escape generator, then comment generator

		$transposed = $this->tokensInLine($source);
		$lines = explode("\n", $source);
		// print_r($transposed);
		// print_r($lines);
		$tabs = $this->tabRef($transposed, $lines);
		$mask = $this->markEscapeIndent($transposed);
		// print_r($tabs);
		// print_r($mask);
		$source = $this->minIndenter($tabs, $lines, $mask);
		// third pass, tabs array, do indenting (check tabs then only modify if needed.)
		// echo "============ assuming php developers are not that stupid to include T_INLINE_HTML in the middle of a php file ============\n";

		return $source;
	}

	private function tabRef($transposed, $lines) {
		$level = 0;
		$tabs = [];
		// print_r($transposed);
		foreach ($transposed as $li => $line) {
			$calc = $this->isPaired($line);
			if (!$calc['paired']) {
				// $open += substr_count($line, '{ ');
				// $close -= substr_count($line, '} ');
				if ($calc['open'] !== 0 || $calc['close'] !== 0) {
					$level += $calc['open'];
					$level -= $calc['close'];
					// echo "$level : $li -> $line \n";
				}

				$tabs[$li] = ($calc['open'] !== 0) ? $level - 1 : $level;
			} else {
				$tabs[$li] = $level;
			}
			if ($level < 0) {
				for ($i = $li - 10; $i < $li; $i++) {
					echo "$i => $lines[$i]\n";
					echo "$i => $transposed[$i]\n";
					echo "$i => $tabs[$i]\n\n";
				}
				echo $calc['open'] . " " . $calc['close'] . " " . "$level : $li -> $line \n";

				throw new Exception('Holy mother of ~, contact me, extremely unlikely');
				die;
			}
		}
		return $tabs;
	}

	private function markEscapeIndent($transposed) {
		$escape = [];
		$seen = false;
		foreach ($transposed as $li => $line) {
			if (preg_match('/T_START_HEREDOC/', $line)) {
				$escape[$li] = ($seen) ? true : false;
				$seen = true;
			} elseif (preg_match('/T_END_HEREDOC/', $line)) {
				$escape[$li] = ($seen) ? true : false;
				$seen = false;

			} else {
				$escape[$li] = ($seen) ? true : false;
			}
		}
		return $escape;
	}

	private function minIndenter($tabs, $lines, $mask) {
		foreach ($tabs as $li => $tabCount) {
			if (!$mask[$li]) {
				$replacement = str_repeat("\t", $tabCount);
				$lines[$li] = preg_replace('/^\t*/', $replacement, $lines[$li]);
				$lines[$li] = preg_replace('/\t[^\t]\$/', "\t\$", $lines[$li]);
			}
		}
		return implode("\n", $lines);
	}

	private function curliesBreaker($source) {
		$lines = explode("\n", $source);
		$transposed = $this->tokensInLine($source);
		foreach ($transposed as $li => $line) {
			$calc = $this->isPaired($line);
			if (!$calc['paired']) {
				if ($calc['open'] != 0) {
					if (!preg_match('/^\s*{$/', $lines[$li])) {
						$lines[$li] = $this->breakCurlies($lines[$li], '{');
					}
				}
				if ($calc['close'] != 0) {
					if (!preg_match('/^\s*}$/', $lines[$li])) {
						$lines[$li] = $this->breakCurlies($lines[$li], '}');
					}
				}
			}
		}
		$lines = implode("\n", $lines);
		// $lines = str_replace("\n\n", "\n", $lines); // ensure no \n\n
		return $lines;
	}

	private function breakCurlies($line, $target) {
		// echo "work $target from -->", $line, "<--from\n";
		$tmp = explode($target, $line);
		$tmp = implode("\n$target\n", $tmp);
		$line = str_replace("\n\n", "\n", $tmp); // be replace only after all these
		$line = preg_replace("/\n$/", '', $line); // trim last \n
		$line = preg_replace("/^\s+\n$target/", $target, $line); // trim last \n
		// echo "work $target to ---->", $line, "<--to\n";
		return $line;
	}

	private function isPaired($line) {

		// $line = '			static::$dispatcher->listen("eloquent.{$event}: {$name}", $callback);';
		$result = [];

		$open = substr_count($line, '{ ');
		$close = substr_count($line, '} ');
		$open += substr_count($line, 'T_CURLY_OPEN ');
		$close += substr_count($line, 'T_CURLY_CLOSE ');

		$result['open'] = $open;
		$result['close'] = $close;

		$internalReplaceOpen = str_replace('{ ', 'T_CURLY_OPEN ', $line);
		$internalReplaceClose = str_replace('} ', 'T_CURLY_CLOSE ', $line);

		if ($open == $close) {
			// need to check if it starts with { else not paired.
			if (strpos($line, 'T_CURLY_OPEN') > strpos($line, 'T_CURLY_CLOSE')) {
				$result['paired'] = false;
			} else {
				$result['paired'] = true;
			}
		} else {
			$result['paired'] = false;
		}
		// echo "$open vs $close";
		// die;
		// return array('paired' => ($open == $close) ? true : false, 'open' => $open, 'close' => $close); // to escape single line multiple paired curlies.
		return $result;
	}

	// private function prependNewLineToCurlies($source) {
	// 	$source = str_replace("{", "\n{", $source);
	// 	$source = str_replace("}", "\n}", $source);
	// 	$source = str_replace("\n\n", "\n", $source);
	// 	return $source;
	// }

	private function tokensInLine($source) {
		// rename to tokens to strings
		$tokens = token_get_all($source);
		// token_get_all always returns non zero line number in token_get_all()[x][2]
		// unless basic character like '{', '}'
		// seriously depend on core function to trim \n+ to \n ??

		$accum = [];
		$lines = explode("\n", $source);
		foreach ($lines as $i => $line) {
			$accum[$i] = ''; // makes it exploded of $source
		}
		$tokens = token_get_all($source);
		$currLineNumber = 1;
		$prevtokenLineNumber = 0;
		while (list($index, $token) = each($tokens)) {
			list($id, $text) = $this->getToken($token);
			if (isset($token[2])) {
				if ($token[2] == $currLineNumber) {
					$accum[$token[2] - 1] .= token_name($token[0]) . ' ';
					// $accum[$currLineNumber - 1] .= token_name($token[0]) . ' '; // not same as token[2]
				} else {
					$currLineNumber++;
					$accum[$token[2] - 1] .= token_name($token[0]) . ' ';
					// $accum[$currLineNumber - 1] .= token_name($token[0]) . ' '; // not same as token[2]
				}
				$prevtokenLineNumber = $token[2];
			} else {
				$nextLineNumber = $this->searchNextTokenWithLineNumber($index + 1, $tokens);
				if ($nextLineNumber == $currLineNumber) {
					$accum[$currLineNumber - 1] .= "$token ";
					// echo "debug $token next$nextLineNumber == curr$currLineNumber needed a\n";
				} elseif ($nextLineNumber > ($currLineNumber)) {
					$accum[$nextLineNumber - 1] .= "$token ";
					// echo "debug $token next$nextLineNumber gt curr$currLineNumber, $prevtokenLineNumber needed b\n";
				} else {
					echo "debug $token next$nextLineNumber lt curr$currLineNumber, $prevtokenLineNumber  needed c\n";
				}
			}
		}
		return $accum;
	}

	private function searchNextTokenWithLineNumber($offset, $tokens) {
		$limitLookAheadBy = 10;
		for ($i = $offset; $i < count($tokens); $i++) {
			if (isset($tokens[$i][2])) {
				return $tokens[$i][2];
			}
			$limitLookAheadBy--;
			if ($limitLookAheadBy <= 0) {
				echo "debug on searchNextTokenWithLineNumber required\n";
				return false;
			}
		}
		return "debug on searchNextTokenWithLineNumber is a must\n";
	}
}
