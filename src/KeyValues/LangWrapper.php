<?php namespace VLib\KeyValues;

use JsonSerializable;

/**
 * A wrapper that lets you easily merge a file using translations with a translation file.
 *
 * @package VLib
 */
class LangWrapper implements JsonSerializable {
	/**
	 * The language tokens.
	 * @var array
	 */
	private $tokens;

	/**
	 * The file data.
	 * @var array
	 */
	private $array;

	/**
	 * Construct a wrapper with the given tokens and values.
	 * @param $tokens
	 * @param $array
	 */
	public function __construct($tokens, $array) {
		$this->tokens = $tokens;
		$this->array = $array;
	}

	/**
	 * Get a (possibly translated) key from the array.
	 * This uses array_get and accepts dot notation.
	 *
	 * @param $key
	 * @param null $default
	 * @return mixed
	 */
	public function get($key, $default = null) {
		return $this->checkLangKey(array_get($this->array, $key, $default));
	}

	/**
	 * Translate a value if needed/possible.
	 * @param $value
	 * @return mixed
	 */
	private function checkLangKey($value) {
		if (is_string($value) && $value[0] == '#') {
			return array_get($this->tokens, substr($value, 1), $value);
		}

		return $value;
	}

	/**
	 * Automatically translate this
	 * @return array
	 */
	function jsonSerialize() {
		$copy = $this->array;
		$self = $this;
		array_walk_recursive($copy, function(&$item, $key) use ($self) {
			if (is_string($item) && $item[0] == '#') {
				$item = $self->checkLangKey($item);
			}
		});
		return $copy;
	}
}