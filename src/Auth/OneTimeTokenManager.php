<?php

namespace OndraKoupil\AppTools\Auth;

use DateTime;
use Exception;

class OneTimeTokenManager {

	private $hashPassword;

	function __construct($hashPassword) {
		$this->hashPassword = $hashPassword;
	}

	function generate(IdentityInterface $identity, DateTime $validUntil, $data) {

		$dataPayload = array(
			'userId' => $identity->getId(),
			'until' => $validUntil->getTimestamp(),
			'data' => $data
		);

		$dataString = json_encode($dataPayload, JSON_THROW_ON_ERROR);

		$hash = $this->hash($dataString);
		$dataPayloadWithHash = $dataPayload + array('hash' => $hash);

		return base64_encode(json_encode($dataPayloadWithHash, JSON_THROW_ON_ERROR));

	}

	/**
	 * @param string $inputString
	 *
	 * @return OneTimeTokenValidationResult
	 */
	function validate($inputString) {

		$decoded = json_decode(base64_decode($inputString), true, 512, JSON_THROW_ON_ERROR);
		if (!$decoded) {
			return new OneTimeTokenValidationResult(false);
		}

		if (!isset($decoded['userId']) or !isset($decoded['until']) or !isset($decoded['data'])) {
			return new OneTimeTokenValidationResult(false);
		}
		if (!is_numeric($decoded['until'])) {
			return new OneTimeTokenValidationResult(false);
		}

		$dataPayload = array(
			'userId' => $decoded['userId'],
			'until' => $decoded['until'],
			'data' => $decoded['data'],
		);
		$dataString = json_encode($dataPayload, JSON_THROW_ON_ERROR);
		$receivedHash = $decoded['hash'];

		try {
			$validUntil = new DateTime();
			$validUntil->setTimestamp($dataPayload['until']);
		} catch (Exception $e) {
			return new OneTimeTokenValidationResult(false);
		}

		if (!$this->verify($dataString, $receivedHash) or $validUntil < new DateTime('now')) {
			return new OneTimeTokenValidationResult(
				false,
				$dataPayload['userId'],
				$validUntil,
				$dataPayload['data']
			);
		}

		return new OneTimeTokenValidationResult(
			true,
			$dataPayload['userId'],
			$validUntil,
			$dataPayload['data']
		);

	}

	protected function hash($value) {
		//password_hash($this->hashPassword . ':' . $dataString, PASSWORD_DEFAULT, array('cost' => 5));
		return substr(hash('sha512', $value, false), 11, 64);
	}

	protected function verify($testedValue, $hash) {
		//password_verify($dataString, $receivedHash)
		return ($this->hash($testedValue) === $hash);
	}


}
