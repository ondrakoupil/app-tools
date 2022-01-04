<?php


namespace OndraKoupil\AppTools\Auth;

use DateInterval;
use DateTime;

interface AuthenticatorInterface {

	public function validateToken(string $token): ValidationResult;

	public function createToken(IdentityInterface $identity, DateTime $now = null): string;

	public function extendToken(string $token, DateInterval $interval, DateTime $now = null);

	public function invalidateToken(string $token);

	public function validateCredentials(string $username, string $password): ?IdentityInterface;

}
