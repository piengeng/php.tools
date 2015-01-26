<?php
class AllmanStyleBraces extends FormatterPass {
	public function candidate($source, $foundTokens) {
		return true;
	}

	public function format($source) {
		$this->tkns = token_get_all($source);
		$this->code = '';
		$foundStack = [];
		$currentIndentation = 0;

		// $lines = $this->tokensInLine($source);
		// $prevKeptLine = -1;
		while (list($index, $token) = each($this->tkns)) {
			list($id, $text) = $this->getToken($token);
			$this->ptr = $index;
			switch ($id) {
				// case T_RETURN:
				// 	// print_r($foundStack);
				// 	// echo "line on $token[2] -->";
				// 	if ($token[2] !== $prevKeptLine) {
				// 		$newText = $this->getLine($this->tkns, $index, $token[2], $this->tkns);
				// 		$prevKeptLine = $token[2];
				// 		$this->appendCode($newText);
				// 	}
				// 	break;
				case T_FUNCTION:
					// if (preg_match('/T_RETURN/', $lines[$token[2] + 1])) {
					// 	// overwritten by T_RETURN case
					// 	// echo "found it??" . $token[2] . " " . $lines[$token[2]] . "\n";
					// 	if ($token[2] !== $prevKeptLine) {
					// 		$newText = $this->getLine($this->tkns, $index, $token[2], $this->tkns);
					// 		$prevKeptLine = $token[2];
					// 		$this->appendCode($newText);
					// 		break;
					// 	}
					// }
					$currentIndentation = 0;
					$poppedID = array_pop($foundStack);
					if (true === $poppedID['implicit']) {
						list($prevId, $prevText) = $this->inspectToken(-1);
						$currentIndentation = substr_count($prevText, $this->indentChar);
					}
					$foundStack[] = $poppedID;
					$this->appendCode($text);
					break;
				case ST_CURLY_OPEN:
					if ($this->leftUsefulTokenIs([ST_PARENTHESES_CLOSE, T_ELSE, T_FINALLY, T_DO])) {
						list($prevId, $prevText) = $this->getToken($this->leftToken());
						if (!$this->hasLn($prevText)) {
							$this->appendCode($this->getCrlfIndent());
						}
					}
					$indentToken = [
						'id' => $id,
						'implicit' => true,
					];
					$adjustedIndendation = max($currentIndentation - $this->indent, 0);
					$this->appendCode(str_repeat($this->indentChar, $adjustedIndendation) . $text);
					$currentIndentation = 0;
					if ($this->hasLnAfter()) {
						$indentToken['implicit'] = false;
						$this->setIndent(+1);
					}
					if (!$this->hasLnAfter() && !$this->leftUsefulTokenIs([T_OBJECT_OPERATOR, T_DOUBLE_COLON])) {
						$this->setIndent(+1);
						$this->appendCode($this->getCrlfIndent());
						$this->setIndent(-1);
					}
					$foundStack[] = $indentToken;
					break;

				case T_DOLLAR_OPEN_CURLY_BRACES:
				case T_CURLY_OPEN:
					if ($this->leftUsefulTokenIs([ST_PARENTHESES_CLOSE, T_ELSE, T_FINALLY, T_DO])) {
						list($prevId, $prevText) = $this->getToken($this->leftToken());
						if (!$this->hasLn($prevText)) {
							$this->appendCode($this->getCrlfIndent());
						}
					}
					$indentToken = [
						'id' => $id,
						'implicit' => true,
					];
					$this->appendCode($text);
					if ($this->hasLnAfter()) {
						$indentToken['implicit'] = false;
						$this->setIndent(+1);
					}
					$foundStack[] = $indentToken;
					break;

				case ST_BRACKET_OPEN:
				case ST_PARENTHESES_OPEN:
					$indentToken = [
						'id' => $id,
						'implicit' => true,
					];
					$this->appendCode($text);
					if ($this->hasLnAfter()) {
						$indentToken['implicit'] = false;
						$this->setIndent(+1);
					}
					$foundStack[] = $indentToken;
					break;

				case ST_BRACKET_CLOSE:
				case ST_PARENTHESES_CLOSE:
				case ST_CURLY_CLOSE:
					$poppedID = array_pop($foundStack);
					if (false === $poppedID['implicit']) {
						$this->setIndent(-1);
					}
					$this->appendCode($text);
					break;

				case T_ELSE:
				case T_ELSEIF:
				case T_FINALLY:
					list($prevId, $prevText) = $this->getToken($this->leftToken());
					if (!$this->hasLn($prevText) && T_OPEN_TAG != $prevId) {
						$this->appendCode($this->getCrlfIndent());
					}
					$this->appendCode($text);
					break;

				case T_CATCH:
					if (' ' == substr($this->code, -1, 1)) {
						$this->code = substr($this->code, 0, -1);
					}
					list($prevId, $prevText) = $this->getToken($this->leftToken());
					if (!$this->hasLn($prevText)) {
						$this->appendCode($this->getCrlfIndent());
					}
					$this->appendCode($text);
					break;

				default:
					$this->appendCode($text);
			}
		}

		return $this->code;
	}

	// private function getLine($rotkns, $idx, $lineNumber, &$tkns) {

	// 	$i = $idx;
	// 	$valid = $lineNumber;
	// 	$since = 0;
	// 	while (--$i >= 0 && ($valid == $lineNumber)) {

	// 		if (isset($rotkns[$i][2]) && $rotkns[$i][2] == $lineNumber) {
	// 			// echo "erm $lineNumber $i\n";
	// 			continue;
	// 		} elseif (isset($rotkns[$i][2]) && $rotkns[$i][2] !== $lineNumber) {
	// 			$since = $i;
	// 			$valid = $lineNumber;
	// 			// echo "mre $lineNumber $i\n";
	// 			break;
	// 		}
	// 	}
	// 	// echo "\n----\n$since <- since\n";

	// 	$i = $idx;
	// 	$valid = $lineNumber;
	// 	$until = 0;
	// 	while (++$i <= count($rotkns) && ($valid == $lineNumber)) {

	// 		if (isset($rotkns[$i][2]) && $rotkns[$i][2] == $lineNumber) {
	// 			// echo "erm $lineNumber $i\n";
	// 			continue;
	// 		} elseif (isset($rotkns[$i][2]) && $rotkns[$i][2] !== $lineNumber) {
	// 			$until = $i;
	// 			$valid = $lineNumber;
	// 			// echo "mre $lineNumber $i\n";
	// 			break;
	// 		}
	// 	}

	// 	// print_r($rotkns[$since]);
	// 	// print_r($rotkns[$until]);

	// 	$sinceTrue = $since;
	// 	$i = $since;
	// 	while (++$i < $until) {
	// 		if (isset($rotkns[$i][2]) && $rotkns[$i][2] == $lineNumber) {
	// 			$sinceTrue = $i;
	// 			break;
	// 		}
	// 	}

	// 	$untilTrue = $until;
	// 	$i = $until;
	// 	while (--$i > $since) {
	// 		if (isset($rotkns[$i][2]) && $rotkns[$i][2] == $lineNumber) {
	// 			$untilTrue = $i;
	// 			break;
	// 		}
	// 	}
	// 	// echo "until => $until\n----\n";

	// 	// print_r($rotkns[$sinceTrue]);
	// 	// print_r($rotkns[$untilTrue]);
	// 	// echo "$sinceTrue $untilTrue\n";

	// 	$line = '';
	// 	for ($j = $sinceTrue; $j <= $untilTrue; $j++) {
	// 		if (isset($rotkns[$j][2])) {
	// 			$line .= $rotkns[$j][1];
	// 		} else {
	// 			$line .= $rotkns[$j];
	// 		}
	// 	}
	// 	// echo "$line \n";
	// 	// $processed = array('line' => $line, 'ln' = )
	// 	while (list($index, $token) = each($tkns)) {
	// 		$this->ptr = $index;
	// 		if ($index == $untilTrue) {
	// 			break;
	// 		}
	// 	}
	// 	return $line;
	// }

	// private function tokensInLine($source) {
	// 	// rename to tokens to strings
	// 	$tokens = token_get_all($source);
	// 	// token_get_all always returns non zero line number in token_get_all()[x][2]
	// 	// unless basic character like '{', '}'
	// 	// seriously depend on core function to trim \n+ to \n ??

	// 	$accum = [];
	// 	$lines = explode("\n", $source);
	// 	foreach ($lines as $i => $line) {
	// 		$accum[$i] = ''; // makes it exploded of $source
	// 	}
	// 	$tokens = token_get_all($source);
	// 	$currLineNumber = 1;
	// 	$prevtokenLineNumber = 0;
	// 	while (list($index, $token) = each($tokens)) {
	// 		list($id, $text) = $this->getToken($token);
	// 		if (isset($token[2])) {
	// 			if ($token[2] == $currLineNumber) {
	// 				$accum[$token[2] - 1] .= token_name($token[0]) . ' ';
	// 				// $accum[$currLineNumber - 1] .= token_name($token[0]) . ' '; // not same as token[2]
	// 			} else {
	// 				$currLineNumber++;
	// 				$accum[$token[2] - 1] .= token_name($token[0]) . ' ';
	// 				// $accum[$currLineNumber - 1] .= token_name($token[0]) . ' '; // not same as token[2]
	// 			}
	// 			$prevtokenLineNumber = $token[2];
	// 		} else {
	// 			$nextLineNumber = $this->searchNextTokenWithLineNumber($index + 1, $tokens);
	// 			if ($nextLineNumber == $currLineNumber) {
	// 				$accum[$currLineNumber - 1] .= "$token ";
	// 				// echo "debug $token next$nextLineNumber == curr$currLineNumber needed a\n";
	// 			} elseif ($nextLineNumber > ($currLineNumber)) {
	// 				$accum[$nextLineNumber - 1] .= "$token ";
	// 				// echo "debug $token next$nextLineNumber gt curr$currLineNumber, $prevtokenLineNumber needed b\n";
	// 			} else {
	// 				echo "debug $token next$nextLineNumber lt curr$currLineNumber, $prevtokenLineNumber  needed c\n";
	// 			}
	// 		}
	// 	}
	// 	return $accum;
	// }

	// private function searchNextTokenWithLineNumber($offset, $tokens) {
	// 	$limitLookAheadBy = 10;
	// 	for ($i = $offset; $i < count($tokens); $i++) {
	// 		if (isset($tokens[$i][2])) {
	// 			return $tokens[$i][2];
	// 		}
	// 		$limitLookAheadBy--;
	// 		if ($limitLookAheadBy <= 0) {
	// 			echo "debug on searchNextTokenWithLineNumber required\n";
	// 			return false;
	// 		}
	// 	}
	// 	return "debug on searchNextTokenWithLineNumber is a must\n";
	// }
}
