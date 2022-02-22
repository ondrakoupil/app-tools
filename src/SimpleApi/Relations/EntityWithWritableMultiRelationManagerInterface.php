<?php

namespace OndraKoupil\AppTools\SimpleApi\Relations;

interface EntityWithWritableMultiRelationManagerInterface extends EntityWithMultiRelationManagerInterface {

	function setRelatedItemsForId(string $id, array $relatedIds, $otherEntityId = '');

	function addRelatedItemsForId(string $id, array $relatedIds, $otherEntityId = '');

	function deleteRelatedItemsForId(string $id, array $relatedIds, $otherEntityId = '');

	function clearRelatedItemsForId(string $id, $otherEntityId = '');

}
