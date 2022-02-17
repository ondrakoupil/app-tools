<?php

namespace OndraKoupil\AppTools\SimpleApi\Relations;

interface AutoExpandingRelatedItemsManagerInterface {
	function setupAutoExpandRelatedItems(
		string $contextKey,
		string $otherEntityId = '',
		$relatedItemExpandContext = array(),
		string $itemFieldToExpandRelatedItems = ''
	);
}
