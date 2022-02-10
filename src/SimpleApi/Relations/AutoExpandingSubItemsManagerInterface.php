<?php

namespace OndraKoupil\AppTools\SimpleApi\Relations;

interface AutoExpandingSubItemsManagerInterface {
	function setupAutoExpandSubItems(string $contextKey, string $childEntityId = '', $childItemExpandContext = array(), $itemFieldToExpandSubitems = '');
}
