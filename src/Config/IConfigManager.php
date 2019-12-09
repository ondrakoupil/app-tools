<?php


namespace OndraKoupil\AppTools\Config;


use ArrayAccess;

interface IConfigManager extends ArrayAccess {

	public function get($key, $default = null);

	public function set($key, $value);

	public function clear($key);

	public function write();

	public function __get($name);

	public function __set($key, $value);

}
