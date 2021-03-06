<?php
final class ReindentObjOps extends FormatterPass {
	const ALIGNABLE_OBJOP = "\x2 OBJOP%d.%d.%d \x3";

	const ALIGN_WITH_INDENT = 1;
	const ALIGN_WITH_SPACES = 2;

	public function candidate($source, $foundTokens) {
		if (isset($foundTokens[T_OBJECT_OPERATOR])) {
			return true;
		}

		return false;
	}

	public function format($source) {
		$this->tkns = token_get_all($source);
		$this->code = '';

		$levelCounter = 0;
		$levelEntranceCounter = [];
		$contextCounter = [];
		$touchCounter = [];
		$alignType = [];
		$printedPlaceholder = [];
		$maxContextCounter = [];

		while (list($index, $token) = each($this->tkns)) {
			list($id, $text) = $this->getToken($token);
			$this->ptr = $index;
			switch ($id) {
				case T_WHILE:
				case T_IF:
				case T_FOR:
				case T_FOREACH:
				case T_SWITCH:
					$this->appendCode($text);
					$this->printUntil(ST_PARENTHESES_OPEN);
					$this->printBlock(ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE);
					break;

				case T_NEW:
					$this->appendCode($text);
					if ($this->leftUsefulTokenIs(ST_PARENTHESES_OPEN)) {
						$foundToken = $this->printUntilAny([ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE, ST_COMMA]);
						if (ST_PARENTHESES_OPEN == $foundToken) {
							$this->incrementCounters($levelCounter, $levelEntranceCounter, $contextCounter, $maxContextCounter, $touchCounter, $alignType, $printedPlaceholder);
							$this->printBlock(ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE);
							$this->printUntilAny([ST_PARENTHESES_CLOSE, ST_COMMA]);
						}
					}
					break;

				case T_FUNCTION:
					$this->appendCode($text);
					if (!$this->rightUsefulTokenIs(T_STRING)) {
						// $this->increment_counters($level_counter, $level_entrance_counter, $context_counter, $max_context_counter, $touch_counter, $align_type, $printed_placeholder);
						$this->printUntil(ST_PARENTHESES_OPEN);
						$this->printBlock(ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE);
						$this->printUntil(ST_CURLY_OPEN);
						$this->printCurlyBlock();
					}
					break;

				case T_VARIABLE:
				case T_STRING:
					$this->appendCode($text);
					if (!isset($levelEntranceCounter[$levelCounter])) {
						$levelEntranceCounter[$levelCounter] = 0;
					}
					if (!isset($contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]])) {
						$contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
						$maxContextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
						$touchCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
						$alignType[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
						$printedPlaceholder[$levelCounter][$levelEntranceCounter[$levelCounter]][$contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]]] = 0;
					}
					break;

				case ST_PARENTHESES_OPEN:
				case ST_BRACKET_OPEN:
					$this->incrementCounters($levelCounter, $levelEntranceCounter, $contextCounter, $maxContextCounter, $touchCounter, $alignType, $printedPlaceholder);
					$this->appendCode($text);
					break;

				case ST_PARENTHESES_CLOSE:
				case ST_BRACKET_CLOSE:
					--$levelCounter;
					$this->appendCode($text);
					break;

				case T_OBJECT_OPERATOR:
					if (0 == $touchCounter[$levelCounter][$levelEntranceCounter[$levelCounter]]) {
						++$touchCounter[$levelCounter][$levelEntranceCounter[$levelCounter]];
						if ($this->hasLnBefore()) {
							$alignType[$levelCounter][$levelEntranceCounter[$levelCounter]] = self::ALIGN_WITH_INDENT;
							$this->appendCode($this->getIndent(+1) . $text);
							$foundToken = $this->printUntilAny([ST_PARENTHESES_OPEN, ST_SEMI_COLON, $this->newLine]);
							if (ST_SEMI_COLON == $foundToken) {
								$this->incrementCounters($levelCounter, $levelEntranceCounter, $contextCounter, $maxContextCounter, $touchCounter, $alignType, $printedPlaceholder);
							} elseif (ST_PARENTHESES_OPEN == $foundToken) {
								$this->incrementCounters($levelCounter, $levelEntranceCounter, $contextCounter, $maxContextCounter, $touchCounter, $alignType, $printedPlaceholder);
								$this->indent_parentheses_content();
							}
						} else {
							$alignType[$levelCounter][$levelEntranceCounter[$levelCounter]] = self::ALIGN_WITH_SPACES;
							if (!isset($printedPlaceholder[$levelCounter][$levelEntranceCounter[$levelCounter]][$contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]]])) {
								$printedPlaceholder[$levelCounter][$levelEntranceCounter[$levelCounter]][$contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]]] = 0;
							}
							++$printedPlaceholder[$levelCounter][$levelEntranceCounter[$levelCounter]][$contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]]];
							$placeholder = sprintf(
								self::ALIGNABLE_OBJOP,
								$levelCounter,
								$levelEntranceCounter[$levelCounter],
								$contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]]
							);
							$this->appendCode($placeholder . $text);
							$foundToken = $this->printUntilAny([ST_PARENTHESES_OPEN, ST_SEMI_COLON, $this->newLine]);
							if (ST_SEMI_COLON == $foundToken) {
								$this->incrementCounters($levelCounter, $levelEntranceCounter, $contextCounter, $maxContextCounter, $touchCounter, $alignType, $printedPlaceholder);
							} elseif (ST_PARENTHESES_OPEN == $foundToken) {
								$this->incrementCounters($levelCounter, $levelEntranceCounter, $contextCounter, $maxContextCounter, $touchCounter, $alignType, $printedPlaceholder);
								$this->injectPlaceholderParenthesesContent($placeholder);
							}
						}
					} elseif ($this->hasLnBefore() || $this->hasLnLeftToken()) {
						++$touchCounter[$levelCounter][$levelEntranceCounter[$levelCounter]];
						if (self::ALIGN_WITH_SPACES == $alignType[$levelCounter][$levelEntranceCounter[$levelCounter]]) {
							++$printedPlaceholder[$levelCounter][$levelEntranceCounter[$levelCounter]][$contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]]];
							$placeholder = sprintf(
								self::ALIGNABLE_OBJOP,
								$levelCounter,
								$levelEntranceCounter[$levelCounter],
								$contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]]
							);
							$this->appendCode($placeholder . $text);
							$foundToken = $this->printUntilAny([ST_PARENTHESES_OPEN, ST_SEMI_COLON, $this->newLine]);
							if (ST_SEMI_COLON == $foundToken) {
								$this->incrementCounters($levelCounter, $levelEntranceCounter, $contextCounter, $maxContextCounter, $touchCounter, $alignType, $printedPlaceholder);
							} elseif (ST_PARENTHESES_OPEN == $foundToken) {
								$this->incrementCounters($levelCounter, $levelEntranceCounter, $contextCounter, $maxContextCounter, $touchCounter, $alignType, $printedPlaceholder);
								$this->injectPlaceholderParenthesesContent($placeholder);
							}
						} else {
							$this->appendCode($this->getIndent(+1) . $text);
							$foundToken = $this->printUntilAny([ST_PARENTHESES_OPEN, ST_SEMI_COLON, $this->newLine]);
							if (ST_SEMI_COLON == $foundToken) {
								$this->incrementCounters($levelCounter, $levelEntranceCounter, $contextCounter, $maxContextCounter, $touchCounter, $alignType, $printedPlaceholder);
							} elseif (ST_PARENTHESES_OPEN == $foundToken) {
								$this->incrementCounters($levelCounter, $levelEntranceCounter, $contextCounter, $maxContextCounter, $touchCounter, $alignType, $printedPlaceholder);
								$this->indent_parentheses_content();
							}
						}
					} else {
						$this->appendCode($text);
					}
					break;

				case T_COMMENT:
				case T_DOC_COMMENT:
					if (
						isset($alignType[$levelCounter]) &&
						isset($levelEntranceCounter[$levelCounter]) &&
						isset($alignType[$levelCounter][$levelEntranceCounter[$levelCounter]]) &&
						($this->hasLnBefore() || $this->hasLnLeftToken())
					) {
						if (self::ALIGN_WITH_SPACES == $alignType[$levelCounter][$levelEntranceCounter[$levelCounter]]) {
							++$printedPlaceholder[$levelCounter][$levelEntranceCounter[$levelCounter]][$contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]]];
							$this->appendCode(
								sprintf(
									self::ALIGNABLE_OBJOP,
									$levelCounter,
									$levelEntranceCounter[$levelCounter],
									$contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]]
								)
							);
						} elseif (self::ALIGN_WITH_INDENT == $alignType[$levelCounter][$levelEntranceCounter[$levelCounter]]) {
							$this->appendCode($this->getIndent(+1));
						}
					}
					$this->appendCode($text);
					break;

				case ST_COMMA:
				case ST_SEMI_COLON:
					if (!isset($levelEntranceCounter[$levelCounter])) {
						$levelEntranceCounter[$levelCounter] = 0;
					}
					++$levelEntranceCounter[$levelCounter];
					$this->appendCode($text);
					break;

				default:
					$this->appendCode($text);
					break;
			}
		}
		$orig_code = $this->code;
		foreach ($maxContextCounter as $level => $entrances) {
			foreach ($entrances as $entrance => $context) {
				for ($j = 0; $j <= $context; ++$j) {
					if (!isset($printedPlaceholder[$level][$entrance][$j])) {
						continue;
					}
					if (0 === $printedPlaceholder[$level][$entrance][$j]) {
						continue;
					}

					$placeholder = sprintf(self::ALIGNABLE_OBJOP, $level, $entrance, $j);
					if (1 === $printedPlaceholder[$level][$entrance][$j]) {
						$this->code = str_replace($placeholder, '', $this->code);
						continue;
					}

					$lines = explode($this->newLine, $this->code);
					$linesWithObjop = [];
					$blockCount = 0;

					foreach ($lines as $idx => $line) {
						if (false !== strpos($line, $placeholder)) {
							$linesWithObjop[] = $idx;
						}
					}

					$farthest = 0;
					foreach ($linesWithObjop as $idx) {
						$farthest = max($farthest, strpos($lines[$idx], $placeholder . '->'));
					}
					foreach ($linesWithObjop as $idx) {
						$line = $lines[$idx];
						$current = strpos($line, $placeholder);
						$delta = abs($farthest - $current);
						if ($delta > 0) {
							$line = str_replace($placeholder, str_repeat(' ', $delta) . $placeholder, $line);
							$lines[$idx] = $line;
						}
					}

					$this->code = str_replace($placeholder, '', implode($this->newLine, $lines));
				}
			}
		}
		return $this->code;
	}

	private function indent_parentheses_content() {
		$count = 0;
		$i = $this->ptr;
		$sizeof_tokens = sizeof($this->tkns);
		for ($i = $this->ptr; $i < $sizeof_tokens; ++$i) {
			$token = &$this->tkns[$i];
			list($id, $text) = $this->getToken($token);
			if (T_WHITESPACE == $id && $this->hasLn($text)) {
				$token[1] = $text . $this->getIndent(+1);
				continue;
			}
			if (ST_PARENTHESES_OPEN == $id) {
				++$count;
			}
			if (ST_PARENTHESES_CLOSE == $id) {
				--$count;
			}
			if (0 == $count) {
				break;
			}
		}
	}

	private function injectPlaceholderParenthesesContent($placeholder) {
		$count = 0;
		$i = $this->ptr;
		$sizeof_tokens = sizeof($this->tkns);
		for ($i = $this->ptr; $i < $sizeof_tokens; ++$i) {
			$token = &$this->tkns[$i];
			list($id, $text) = $this->getToken($token);
			if (T_WHITESPACE == $id && $this->hasLn($text)) {
				$token[1] = str_replace($this->newLine, $this->newLine . $placeholder, $text);
				continue;
			}
			if (ST_PARENTHESES_OPEN == $id) {
				++$count;
			}
			if (ST_PARENTHESES_CLOSE == $id) {
				--$count;
			}
			if (0 == $count) {
				break;
			}
		}
	}

	private function incrementCounters(
		&$levelCounter,
		&$levelEntranceCounter,
		&$contextCounter,
		&$maxContextCounter,
		&$touchCounter,
		&$alignType,
		&$printedPlaceholder
	) {
		++$levelCounter;
		if (!isset($levelEntranceCounter[$levelCounter])) {
			$levelEntranceCounter[$levelCounter] = 0;
		}
		++$levelEntranceCounter[$levelCounter];
		if (!isset($contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]])) {
			$contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
			$maxContextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
			$touchCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
			$alignType[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
			$printedPlaceholder[$levelCounter][$levelEntranceCounter[$levelCounter]][$contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]]] = 0;
		}
		++$contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]];
		$maxContextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = max($maxContextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]], $contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]]);

	}
}
