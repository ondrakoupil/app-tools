<?php

namespace OndraKoupil\AppTools\Config;

abstract class BaseManager implements IConfigManager {

	public function offsetExists($offset) {
		return ($this->get($offset) !== null);
	}

	public function offsetGet($offset) {
		return $this->get($offset);
	}

	public function offsetSet($offset, $value) {
		return $this->set($offset, $value);
	}

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
