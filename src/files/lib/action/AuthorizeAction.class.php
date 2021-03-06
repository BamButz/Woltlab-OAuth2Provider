<?php

namespace wcf\action;

use wcf\data\oauth2server\AuthClient;
use wcf\system\exception\IllegalLinkException;
use wcf\system\oauth2server\TokenService;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\HeaderUtil;

/**
 * Handles OAuth2 authorization requests
 *
 * @author    Cryptonica
 * @copyright 2018 Cryptonica
 * @package    Cryptonica\OAuth2Server\Action
 */
class AuthorizeAction extends AbstractAction {

	private $clientID = null;
	private $responseType = null;
	private $redirectUri = null;
	private $state = null;

	/**
	 * @inheritdoc
	 */
	public function readParameters() {
		parent::readParameters();

		if (isset($_GET["client_id"]))
			$this->clientID = $_GET["client_id"];
		
		if (isset($_GET["response_type"]))
			$this->responseType = $_GET["response_type"];

		if (isset($_GET["redirect_uri"]))
			$this->redirectUri = $_GET["redirect_uri"];

		if (isset($_GET["state"]))
			$this->state = $_GET["state"];
	}

	/**
	 * @inheritdoc
	 */
	public function execute() {
		parent::execute();

		// Ohne ClientID ist der anfordernde Dienst nicht zu erkennen
		if ($this->clientID == null)
			throw new IllegalLinkException();

		// Wir unterstützen für den Anfang nur den Authorization Code Flow
		if ($this->responseType != "code")
			throw new IllegalLinkException();

		// Existiert ein Client mit dieser ID?
		$client = new AuthClient($this->clientID);
		if ($client->getObjectID() === 0)
			throw new IllegalLinkException();
		
		// Wenn Benutzer nicht eingeloggt, muss dieser sich erst einmal einloggen
		if (!WCF::getUser()->userID) {
			$requestUri = WCF::getRequestURI();
			$url = LinkHandler::getInstance()->getLink("Login", ["url" => $requestUri]);

			HeaderUtil::redirect($url);
			$this->executed();
			exit;
		}

		// TODO: Consent

		// Token generieren
		$authCode = TokenService::createAuthorizationCode($this->clientID, WCF::getUser()->userID);

		// TODO: URL mit hinterlegter Adresse prüfen sowie sicherer Uri Erstellung
		$redirectUri = $client->callbackUrl;
		$redirectUri .= (parse_url($redirectUri, PHP_URL_QUERY) ? '&' : '?') . 'state=' . $this->state . '&code=' . $authCode->token;

		HeaderUtil::redirect($redirectUri);
		$this->executed();
		exit;
	}
}
