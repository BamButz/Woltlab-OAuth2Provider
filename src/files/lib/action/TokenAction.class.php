<?php
/**
 * Created by PhpStorm.
 * User: b.just
 * Date: 10.09.2018
 * Time: 22:12
 */

namespace wcf\action;


use wcf\data\oauth2server\AuthToken;
use wcf\system\exception\IllegalLinkException;
use wcf\system\oauth2server\TokenService;

class TokenAction extends AbstractAction {

	private $client = null;
	private $secret = null;
	private $grantType = null;
	private $code = null;

	/**
	 * @inheritDoc
	 */
	public function readParameters() {
		parent::readParameters(); // TODO: Change the autogenerated stub

		if (isset($_GET["grant_type"]))
			$this->grantType = $_GET["grant_type"];

		if(isset($_GET["code"]))
			$this->code = $_GET["code"];
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		parent::execute(); // TODO: Change the autogenerated stub

		if($this->grantType == null)
			throw new IllegalLinkException();

		if($this->code == null)
			throw new IllegalLinkException();

		$authCode = new AuthToken($this->code);
		if($authCode->getObjectID() === 0)
			throw new IllegalLinkException();

		if($authCode->expires < time())
			throw new IllegalLinkException();

		$refreshToken = TokenService::createRefreshToken($authCode->clientID, $authCode->userID);
		$accessToken = TokenService::createAccessToken($authCode->clientID, $authCode->userID);

		$json = new \stdClass();
		$json->access_token = $accessToken;
		$json->refresh_token = $refreshToken;
		$json->expires_in = $accessToken->expires_in;
		$json->token_type = "bearer";

		@header('Content-type: application/json');
		echo json_encode($json, JSON_PRETTY_PRINT);

		$this->executed();
		exit;
	}
}
