<?php

namespace OndraKoupil\AppTools\Config;

abstract class BaseManager implements IConfigManager {

	public function offsetExists($offset): bool {
		return ($this->get($offset) !== null);
	}

	#[\ReturnTypeWillChange]
	public function offsetGet($offset) {
		return $this->get($offset);
	}

	#[\ReturnTypeWillChange]
	public function offsetSet($offset, $value) {
		return $this->set($offset, $value);
	}

	#[\ReturnTypeWillChange]
	public function offsetUnset($offset) {
		return $this->clear($offset);
	}

	public function __get($name) {
		return $this->get($name, null);
	}

	public function __set($key, $value) {
		return $this->set($key, $value);
	}

}
