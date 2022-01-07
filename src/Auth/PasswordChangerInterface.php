<?php


namespace OndraKoupil\AppTools\Auth;

interface PasswordChangerInterface {

	public function changePassword(IdentityInterface $identity, string $newPassword): void;

}
