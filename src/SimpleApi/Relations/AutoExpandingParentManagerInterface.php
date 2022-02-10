<?php

namespace OndraKoupil\AppTools\SimpleApi\Relations;

interface AutoExpandingParentManagerInterface {
	function setupAutoExpandParent(string $contextKey, string $parentEntityId = '', $parentItemExpandContext = array(), $itemFieldToExpandParent = '');
}
