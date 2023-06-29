<?php


namespace OndraKoupil\AppTools\Auth;

interface UserAccountChangerInterface {

	/**
	 * @param IdentityInterface $identity
	 * @param array $newData
	 *
	 * @return IdentityInterface
	 */
	public function changeUserAccount(IdentityInterface $identity, array $newData): IdentityInterface;

}
