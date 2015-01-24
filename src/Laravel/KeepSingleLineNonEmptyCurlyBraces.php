<?php
class KeepSingleLineNonEmptyCurlyBraces extends FormatterPass {
	public function candidate($source, $foundTokens) {
		if (isset($foundTokens[T_RETURN]) && isset($foundTokens[ST_CURLY_OPEN])) {
			return true;
		}
		return false;
	}

	public function format($source) {
		$tknLines = $this->tokensInLine($source);
		$lines = explode("\n", $source);
		// print_r($tknLines);
		// echo "===================================\n";
		// print_r(explode("\n", $source));
		// possible bug from core or previous function, fix by implementing this format function
		// extremely prone to bug
		$theLines = [];

		for ($i = 0; $i < count($tknLines); $i++) {
			$currLineAddZero = isset($tknLines[$i]) ? $tknLines[$i] : '';
			$currLineAddOne = isset($tknLines[$i + 1]) ? $tknLines[$i + 1] : '';
			$currLineAddTwo = isset($tknLines[$i + 2]) ? $tknLines[$i + 2] : '';
			// very specific case/bug
			if (
				substr($currLineAddZero, 0, strlen('T_RETURN')) == 'T_RETURN' and
				strpos($currLineAddZero, ' { ') > -1 and // at least one curly open {
				substr($currLineAddOne, 0, strlen('T_WHITESPACE')) == 'T_WHITESPACE' and
				substr($currLineAddTwo, 0, strlen('T_RETURN')) == 'T_RETURN' and
				strpos($currLineAddTwo, ' } ') > -1// at least one curly close }
			) {
				$merged_dirty = $lines[$i] . $lines[$i + 1] . $lines[$i + 2];
				$merged_cleaned = str_replace("\t", " ", $merged_dirty);
				$merged_cleaned = str_replace("){", ") {", $merged_cleaned);
				$merged_cleaned = str_replace(";}", "; }", $merged_cleaned);

				array_push($theLines, $merged_cleaned);
				$i += 2;
			} else {
				array_push($theLines, $lines[$i]);
			}
		}

		return implode("\n", $theLines) . "\n"; // last linefeed
	}

	private function tokensInLine($source) {
		//remove after study
		$tokens = token_get_all($source);
		$processed = [];
		$seen = 1;
		$tokensLine = '';
		foreach ($tokens as $index => $token) {
			if (isset($token[2])) {
				$currLine = $token[2];
				if ($seen != $currLine) {
					$processed[($seen - 1)] = $tokensLine;
					$tokensLine = token_name($token[0]) . " ";
					$seen = $currLine;
				} else {
					$tokensLine .= token_name($token[0]) . " ";
				}
			} else {
				$tokensLine .= $token . " ";
			}
		}
		$processed[($seen - 1)] = $tokensLine;
		return $processed;
	}
}
