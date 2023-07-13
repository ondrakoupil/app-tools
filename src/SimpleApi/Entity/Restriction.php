<?php

namespace OndraKoupil\AppTools\SimpleApi\Entity;

use InvalidArgumentException;

class Restriction {

	protected $restrictions = array();

	function __construct($initialRestriction = null) {
		if ($initialRestriction) {
			$this->addRestriction($initialRestriction);
		}
	}

	public function getRestrictions() {
		return $this->restrictions ?: null;
	}

	public function addRestriction($restriction) {
		$this->restrictions[] = $restriction;
	}

	public function mergeWithRestrictions(?Restriction $anotherRestriction = null): self {
		if ($anotherRestriction) {
			foreach ($anotherRestriction->getRestrictions() as $r) {
				$this->addRestriction($r);
			}
		}
		return $this;
	}

	public static function createFromRestrictions(...$inputRestrictions): Restriction {
		$r = new Restriction();
		foreach ($inputRestrictions as $restriction) {
			if ($restriction) {
				if ($restriction instanceof Restriction) {
					$r->mergeWithRestrictions($restriction);
				} else {
					throw new InvalidArgumentException('createFromRestrictions can accept only Restriction objects or nulls');
				}
			}
		}
		return $r;
	}

}
