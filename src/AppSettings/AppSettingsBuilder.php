<?php

namespace OndraKoupil\AppTools\AppSettings;

use Nette\Neon\Neon;
use RuntimeException;

class AppSettingsBuilder {

	/**
	 * @var string[]
	 */
	protected $files;

	/**
	 * @var string
	 */
	protected $class;

	/**
	 * @var string[]
	 */
	protected $subItems = array();

	function __construct($class, $files) {
		$this->class = $class;
		$this->files = $files;
	}

	/**
	 * @return string[]
	 */
	public function getFiles() {
		return $this->files;
	}

	/**
	 * @param string[] $files
	 */
	public function setFiles($files) {
		$this->files = $files;
	}

	/**
	 * @return string
	 */
	public function getClass() {
		return $this->class;
	}

	/**
	 * @param string $class
	 */
	public function setClass($class) {
		$this->class = $class;
	}


	/**
	 * @param string $itemName
	 * @param string $class
	 *
	 * @return void
	 */
	public function addSubitemClass($itemName, $class) {
		$this->subItems[$itemName] = $class;
	}

	/**
	 * @return mixed
	 */
	public function createAppSettings() {

		$data = array();
		foreach ($this->files as $file) {
			if (!file_exists($file)) {
				throw new RuntimeException('Not found: ' . $file);
			}
			$content = array();
			if (preg_match('~\.neon$~i', $file)) {
				$content = Neon::decode(file_get_contents($file));
			} elseif (preg_match('~\.json$~i', $file)) {
				$content = json_decode($file, true);
				if ($content === false) {
					throw new RuntimeException('Failed parsing JSON from ' . $file . ' - ' . json_last_error() . ' ' . json_last_error_msg());
				}
			} else {
				throw new RuntimeException('File ' . $file . ' is in unknown format.');
			}

			if ($content) {
				$data += $content;
			}
		}

		$settings = new $this->class;

		$fields = get_object_vars($settings);

		foreach ($fields as $f => $val) {
			if (array_key_exists($f, $data)) {
				$v = $data[$f];
			} else {
				if ($val === null) {
					throw new RuntimeException('App settings missing field: ' . $f);
				}
				$v = $val;
			}
			if (isset($this->subItems[$f]) and $this->subItems[$f]) {
				$classV = new $this->subItems[$f];
				foreach (get_object_vars($classV) as $classVName => $classVVal) {
					if (!$classVVal and !array_key_exists($classVName, $v)) {
						throw new RuntimeException('App settings missing field: ' . $f . '->' . $classVName);
					}
					if (array_key_exists($classVName, $v)) {
						$classV->$classVName = $v[$classVName];
					}
				}
				$v = $classV;
			}
			$settings->$f = $v;
		}

		return $settings;

	}

}
