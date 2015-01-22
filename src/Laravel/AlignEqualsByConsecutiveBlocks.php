<?php
class AlignEqualsByConsecutiveBlocks extends AdditionalPass {
	public function candidate($source, $foundTokens) {
		if (isset($foundTokens[T_USE])) {
			return true;
		}
		return false;
	}

	public function format($source) {
		return $this->alignConsecutiveEqualSign($source);
	}

	private function alignConsecutiveEqualSign($source) {
		// should align '= and '=>'
		$digFromHere = $this->tokensInLine($source);

		// print_r(explode("\n", $source));
		// echo "===================================\n";
		// print_r($digFromHere);

		$seenEquals = [];
		$seenDoubleArrows = [];
		foreach ($digFromHere as $index => $line) {
			if (preg_match('/^T_VARIABLE T_WHITESPACE =.+;/', $line, $match)) {
				array_push($seenEquals, $index);
			}
			if (preg_match('/^(?:T_WHITESPACE )?(T_CONSTANT_ENCAPSED_STRING|T_VARIABLE) T_WHITESPACE T_DOUBLE_ARROW /', $line, $match) and
				!strstr($line, 'T_ARRAY ( ')) {
				array_push($seenDoubleArrows, $index);
			}
		}
		// print_r($seenEquals);
		// print_r($seenDoubleArrows);
		$source = $this->generateConsecutiveFromArray($seenEquals, $source);
		$source = $this->generateConsecutiveFromArray($seenDoubleArrows, $source);

		return $source;
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

	private function generateConsecutiveFromArray($seenArray, $source) {
		$lines = explode("\n", $source);
		// print_r($this->getConsecutiveFromArray($seenArray));
		foreach ($this->getConsecutiveFromArray($seenArray) as $bucket) {
			//get max position of =
			$maxPosition = 0;
			$eq = ' =';
			$toBeSorted = [];
			foreach ($bucket as $indexInBucket) {
				// echo "$indexInBucket(", strpos($lines[$indexInBucket], $eq), ') ';
				$position = strpos($lines[$indexInBucket], $eq);
				$maxPosition = max($maxPosition, $position);
				array_push($toBeSorted, $position);
			}
			// echo ' ', $maxPosition, PHP_EOL;

			// find alternative max if there's a further = position
			// ratio of highest : second highest > 1.5, else use the second highest
			// just run the top 5 to seek the alternative
			rsort($toBeSorted);
			// print_r($toBeSorted);
			for ($i = 1; $i <= 5; $i++) {
				if (isset($toBeSorted[$i])) {
					if ($toBeSorted[($i - 1)] / $toBeSorted[$i] > 1.5) {
						$maxPosition = $toBeSorted[$i];
						break;
					}
				}
			}
			// insert space directly
			foreach ($bucket as $indexInBucket) {
				$delta = $maxPosition - strpos($lines[$indexInBucket], $eq);
				if ($delta > 0) {
					$replace = str_repeat(' ', $delta) . $eq;
					$lines[$indexInBucket] = preg_replace("/$eq/", $replace, $lines[$indexInBucket]);
				}
				// echo $lines[$indexInBucket], PHP_EOL;
			}
			// break;
		}
		// print_r($this->getConsecutiveFromArray($seenDoubleArrows));
		return implode("\n", $lines); //$source;
	}

	private function getConsecutiveFromArray($seenArray) {
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

	/**
	 * @codeCoverageIgnore
	 */
	public function getDescription() {
		return 'A different alignment algorithm for Laravel';
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getExample() {
		return <<<'EOT'
<?php
$a = 1; // basic
$bb = 22;
$ccc = 333;

$a = 1; // with ratio of 1.5
$bb = 22;
$eeeee = 55555;

$hanging = 'hanging no align'; // not counted as a block

$something = array(
    $bb => 22, // intro whitespace
    $ccc => 333,

    'bb' => 22,
    'ccc' => 333,
    'dddd' => 4444,
);
?>
to
<?php
$a   = 1; // basic
$bb  = 22;
$ccc = 333;

$a  = 1; // with ratio of 1.5
$bb = 22;
$eeeee = 55555;

$hanging = 'hanging no align'; // not counted as a block

$something = array(
    $bb  => 22, // intro whitespace
    $ccc => 333,

    'bb'   => 22,
    'ccc'  => 333,
    'dddd' => 4444,
);
?>
EOT;
	}
}
