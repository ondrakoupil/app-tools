<?php


namespace OndraKoupil\AppTools\Auth;

use DateInterval;
use DateTime;

interface Authenticator {

	public function validateToken(string $token): ValidationResult;

	public function createToken(Identity $identity, DateTime $now = null): string;

	public function extendToken(string $token, DateInterval $interval, DateTime $now = null);

	public function invalidateToken(string $token);

	public function validateCredentials(string $username, string $password): ?Identity;

}
