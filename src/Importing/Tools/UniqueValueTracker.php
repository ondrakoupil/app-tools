<?php

namespace OndraKoupil\AppTools\Importing\Tools;

use RuntimeException;

/**
 * Pomůcka pro sledování nějaké hodnoty, která musí být jedinečná.
 */
class UniqueValueTracker {

	protected $nextValueCallback;

	protected $values = array();

	/**
	 * Funkce pro odvození nové hodnoty, když je ta výchozí už zabraná.
	 *
	 * @param callable $nextValueCallback function ($initialValue, $number) => string
	 *
	 * @return void
	 */
	public function setNextValueCallback(callable $nextValueCallback) {
		$this->nextValueCallback = $nextValueCallback;
	}

	/**
	 * Přidá hodnoty, které již jsou zabrané
	 *
	 * @param string[] $values
	 *
	 * @return void
	 */
	public function addValues($values) {
		foreach ($values as $value) {
			$this->values[$value] = true;
		}
	}

	/**
	 * Navrhne vhodnou dosud nevyužitou hodnotu.
	 *
	 * @param string $initialValue Výchozí požadovaná hodnota
	 *
	 * @return string Unikátní hodnota
	 */
	public function getFreeValue($initialValue) {
		if (!isset($this->values[$initialValue])) {
			$this->values[$initialValue] = true;
			return $initialValue;
		}

		$number = 2;
		do {

			if ($this->nextValueCallback) {
				$proposedValue = call_user_func_array($this->nextValueCallback, array($initialValue, $number));
			} else {
				$proposedValue = $initialValue . '-' . $number;
			}

			$number++;

			if ($number > 1000) {
				throw new RuntimeException('Can not find free unique value.');
			}
		} while (isset($this->values[$proposedValue]));

		$this->values[$proposedValue] = true;
		return $proposedValue;
	}

}
