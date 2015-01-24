<?php
class SpaceBetweenBracketVsVariableReturnBreakString extends FormatterPass {
	public function candidate($source, $foundTokens) {
		if (isset($foundTokens[ST_PARENTHESES_CLOSE])) {
			return true;
		}
		return false;
	}

	public function format($source) {
		$this->tkns = token_get_all($source);
		$this->code = '';
		$prevToken = [];
		while (list($index, $token) = each($this->tkns)) {
			list($id, $text) = $this->getToken($token);
			$this->ptr = $index;
			switch ($id) {
				case T_RETURN:
					if ($this->leftTokenIs(ST_PARENTHESES_CLOSE) || $this->leftTokenIs(ST_SEMI_COLON)) {
						if (isset($prevToken[2]) and isset($token[2])) {
							if ($prevToken[2] !== $token[2]) {
								// echo "$prevToken[2] not same line as $token[2]\n";
								$this->appendCode($text);
							}
						} else {
							$this->appendCode(' ' . $text);
						}
					} else {
						$this->appendCode($text);
					}
					break;
				case T_VARIABLE:
					if ($this->leftTokenIs(ST_PARENTHESES_CLOSE)) {
						$this->appendCode(' ' . $text);
					} else {
						$this->appendCode($text);
					}
					break;
				case T_BREAK:
					if ($this->leftTokenIs(ST_PARENTHESES_CLOSE)) {
						$this->appendCode(' ' . $text);
					} else {
						$this->appendCode($text);
					}
					break;
				case T_STRING:
					if ($this->leftTokenIs(ST_PARENTHESES_CLOSE)) {
						$this->appendCode(' ' . $text);
					} else {
						$this->appendCode($text);
					}
					break;
				default:
					$this->appendCode($text);
			}
			$prevToken = $token;
		}
		return $this->code;
	}
}
