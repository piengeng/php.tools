<?php
class KeepEmptyCurlyBraces extends FormatterPass {
	public function candidate($source, $foundTokens) {
		if (isset($foundTokens[ST_CURLY_CLOSE])) {
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
				case ST_CURLY_CLOSE:
					if ($this->leftTokenIs(ST_CURLY_OPEN)) {
						$this->code = preg_replace('/{\s+$/', '{', $this->code);
					}
					$this->appendCode($text);
					break;
				default:
					$this->appendCode($text);
			}
			$prevToken = $token;
		}
		return $this->code;
	}
}
