<?php

namespace OndraKoupil\AppTools\SimpleApi\Relations;

interface EditableRelatedItemsManagerInterface {

	function setupEditableRelatedItems(
		string $keyInInputSet,
		string $keyInInputAdd = '',
		string $keyInInputDelete = '',
		string $keyInInputClear = '',
		string $otherEntityId = ''
	);

}
