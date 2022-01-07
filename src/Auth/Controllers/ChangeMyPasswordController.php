<?php

namespace OndraKoupil\AppTools\Auth\Controllers;

use Exception;
use OndraKoupil\AppTools\Auth\AuthenticatorInterface as Authenticator;
use OndraKoupil\AppTools\Auth\IdentityInterface;
use OndraKoupil\AppTools\Auth\PasswordChangerInterface;
use OndraKoupil\AppTools\Middleware\AuthMiddleware;
use OndraKoupil\AppTools\Middleware\ExtractTokenMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ChangeMyPasswordController extends BaseAuthController {

	const NEW_PASSWORD = 'newPassword';

	/**
	 * @var PasswordChangerInterface
	 */
	private $passwordChanger;

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
	private $newPasswordParameterName;

	/**
	 * @param PasswordChangerInterface $passwordChanger
	 * @param string $userAttributeName
	 * @param LoggerInterface|null $logger
	 * @param string $newPasswordParameterName
	 */
	public function __construct(
		PasswordChangerInterface $passwordChanger,
		string $userAttributeName = AuthMiddleware::USER,
		LoggerInterface $logger = null,
		string $newPasswordParameterName = self::NEW_PASSWORD
	) {
		$this->passwordChanger = $passwordChanger;
		$this->userAttributeName = $userAttributeName;
		$this->logger = $logger;
		$this->newPasswordParameterName = $newPasswordParameterName;
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
		$newPassword = $body[$this->newPasswordParameterName] ?? '';
		if (!$newPassword) {
			throw new Exception('Missing new password in body in parameter ' . $this->newPasswordParameterName, 400);
		}

		$this->passwordChanger->changePassword($user, $newPassword);

		if ($this->logger) {
			$this->logger->info('Changing password for user ' . $user->getId() . ' to ' . str_repeat('*', mb_strlen($newPassword)));
		}

		return $response->withStatus(204);

	}

}
