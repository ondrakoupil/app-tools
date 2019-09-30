<?php

namespace OndraKoupil\AppTools\Auth;

interface Identity {

	public function getId(): string;
	public function toArray(): array;

}
