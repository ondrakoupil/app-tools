<?php

namespace OndraKoupil\AppTools\Auth\Controllers;

use Exception;
use OndraKoupil\AppTools\Auth\AuthenticatorInterface as Authenticator;
use OndraKoupil\AppTools\Auth\IdentityInterface;
use OndraKoupil\AppTools\Auth\PasswordChangerInterface;
use OndraKoupil\AppTools\Auth\UserAccountChangerInterface;
use OndraKoupil\AppTools\Middleware\AuthMiddleware;
use OndraKoupil\AppTools\Middleware\ExtractTokenMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ChangeMyAccountController extends BaseAuthController {

	const USER_FIELD = 'user';

	/**
	 * @var UserAccountChangerInterface
	 */
	private $accountChanger;

	/**
	 * @var string
	 */
	private $userAttributeName;

	/**
	 * @var LoggerInterface|null
	 */
	private $logger;

	/**
	 * @var string
	 */
	private $newUserDataParameterName;

	/**
	 * @var array
	 */
	private $allowedFields;

	/**
	 * @var callable|null
	 */
	private $dataValidatorFunction;

	/**
	 * @param UserAccountChangerInterface $accountChanger
	 * @param array|null $allowedFields
	 * @param callable|null $dataValidatorFunction ($newUserData, $userIdentity) => boolean
	 * @param LoggerInterface|null $logger
	 * @param string $userAttributeName
	 * @param string $newUserDataParameterName
	 */
	public function __construct(
		UserAccountChangerInterface $accountChanger,
		array $allowedFields = null,
		callable $dataValidatorFunction = null,
		LoggerInterface $logger = null,
		string $userAttributeName = AuthMiddleware::USER,
		string $newUserDataParameterName = self::USER_FIELD
	) {
		$this->userAttributeName = $userAttributeName;
		$this->logger = $logger;
		$this->newUserDataParameterName = $newUserDataParameterName;
		$this->allowedFields = $allowedFields;
		$this->dataValidatorFunction = $dataValidatorFunction;
		$this->accountChanger = $accountChanger;
	}

	function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
		return $this->run($request, $response);
	}

	function run(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {

		$user = $request->getAttribute($this->userAttributeName);
		if (!$user or !($user instanceof IdentityInterface)) {
			throw new Exception('Request is not authenticated.', 400);
		}

		$body = $request->getParsedBody();
		$newData = $body[$this->newUserDataParameterName] ?? array();
		if (!$newData) {
			throw new Exception('Missing new data in body in parameter ' . $this->newUserDataParameterName, 400);
		}

		if ($this->allowedFields) {
			$allowedFieldsInverted = array_fill_keys($this->allowedFields, true);
			foreach ($newData as $index => $value) {
				if (!($allowedFieldsInverted[$index] ?? false)) {
					throw new Exception('This field is not allowed in changed data: ' . $index);
				}
			}
		}

		if ($this->dataValidatorFunction) {
			$validator = $this->dataValidatorFunction;
			$ok = $validator($newData, $user);
			if ($ok === false) {
				throw new Exception('Passed data is not valid.');
			}
		}

		$newIdentity = $this->accountChanger->changeUserAccount($user, $newData);

		if ($this->logger) {
			$this->logger->info('Changing account data for user ' . $user->getId() . ' to ' . print_r($newIdentity->toArray(), true));
		}

		return $this->respondWith($response, array('user' => $newIdentity->toArray()));

	}

}
