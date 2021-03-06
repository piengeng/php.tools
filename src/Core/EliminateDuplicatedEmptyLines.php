<?php
final class EliminateDuplicatedEmptyLines extends FormatterPass {
	const EMPTY_LINE = "\x2 EMPTYLINE \x3";

	public function candidate($source, $foundTokens) {
		return true;
	}

	public function format($source) {
		$this->tkns = token_get_all($source);
		$this->code = '';
		$parenCount = 0;
		$bracketCount = 0;
		while (list($index, $token) = each($this->tkns)) {
			list($id, $text) = $this->getToken($token);
			$this->ptr = $index;
			switch ($id) {
				case T_WHITESPACE:
					$text = str_replace($this->newLine, self::EMPTY_LINE . $this->newLine, $text);
					$this->appendCode($text);
					break;
				default:
					$this->appendCode($text);
					break;
			}
		}

		$lines = explode($this->newLine, $this->code);
		$emptyLines = [];
		$blockCount = 0;

		foreach ($lines as $idx => $line) {
			if (trim($line) === self::EMPTY_LINE) {
				$emptyLines[$blockCount][] = $idx;
			} else {
				++$blockCount;
				$emptyLines[$blockCount] = [];
			}
		}

		foreach ($emptyLines as $group) {
			array_pop($group);
			foreach ($group as $lineNumber) {
				unset($lines[$lineNumber]);
			}
		}

		$this->code = str_replace(self::EMPTY_LINE, '', implode($this->newLine, $lines));

		list($id, $text) = $this->getToken(array_pop($this->tkns));
		if (T_WHITESPACE === $id && '' === trim($text)) {
			$this->code = rtrim($this->code) . $this->newLine;
		}

		return $this->code;
	}
}