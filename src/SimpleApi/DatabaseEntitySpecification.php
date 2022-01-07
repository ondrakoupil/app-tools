<?php

namespace OndraKoupil\AppTools\SimpleApi;

class DatabaseEntitySpecification {

	public array $uniqueFields = array();
	public array $uniqueOrEmptyFields = array();

	public $beforeSaveCallback;

}
