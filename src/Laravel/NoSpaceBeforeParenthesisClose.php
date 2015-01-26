<?php
class NoSpaceBeforeParenthesisClose extends FormatterPass {
	public function candidate($source, $foundTokens) {
		if (isset($foundTokens[ST_PARENTHESES_CLOSE])) {
			return true;
		}
		return false;
	}

	public function format($source) {
		$this->tkns = token_get_all($source);
		$this->code = '';

		while (list($index, $token) = each($this->tkns)) {
			list($id, $text) = $this->getToken($token);
			$this->ptr = $index;
			switch ($id) {
				case ST_PARENTHESES_CLOSE:
					if (substr($this->code, -1) == ' ') {
						$this->code = preg_replace('/ $/', $text, $this->code);
					} else {
						$this->appendCode($text);
					}
					break;
				default:
					$this->appendCode($text);
					break;
			}
		}

		return $this->code;
	}
}
