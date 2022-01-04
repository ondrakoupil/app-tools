<?php

namespace OndraKoupil\AppTools\Auth;

interface IdentityInterface {

	public function getId(): string;
	public function toArray(): array;

}
