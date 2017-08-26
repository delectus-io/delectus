<?php

class DelectusCURLTransport extends DelectusTransport implements DelectusHTTPTransportInterface {
	/**
	 * raw options for curl request incase you need to tinker to get working in
	 * your environment. Try to keep them safe!
	 *
	 * @var array
	 */
	private static $curl_options = [
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_PORT           => 443,
		CURLOPT_USE_SSL        => true,
	];
	// these will be added to request to delectus as headers (as value for the CURLOPT_HEADER key in curl options).
	private static $curl_headers = [

	];
	/** @var string user agent to send with requests */
	private static $user_agent = 'Delectus-{module} {version}';

	/**
	 * Name of openssl encryption algorythm to use to encrypt/decrypt request and response data (set to empty to not encrypt data)
	 *
	 * @var string
	 */
	private static $encryption_algorythm = '';

	/**
	 * Pass site and auth tokens on the url when making requests, e.g. if X-Client-Auth and X-Client-Site headers aren't being passed via proxy or some such
	 *
	 * @var bool
	 */
	private static $tokens_in_url = false;

	/** @var \DelectusModule */
	protected $module;

	public function __construct( DelectusModule $module ) {
		$this->module = $module;
		parent::__construct();
	}

	/**
	 * Make request to a Delectus endpoint/action passing any data.
	 *
	 * If config.tokens_in_url are specified will also add 'at' param to querystring for the auth token.
	 *
	 * @param DelectusApiRequestModel $request incoming request parameters, updated to reflect results. written to database.
	 *
	 * @param                         $resultCode
	 * @param                         $resultMessage
	 *
	 * @return bool
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 * @throws \ValidationException
	 * @internal param array $data optional data to add to request payload
	 */
	public function makeRequest( DelectusApiRequestModel $request, &$resultCode, &$resultMessage) {
		$ch       = null;
		$response = null;

		$resultCode = null;
		$resultMessage = null;

		try {


			// make sure request is fully initialised
			if ( ! $request->isInDB() ) {
				$request->write();
			}

			$url = $this->endpoint( $request->Endpoint, $request->Action );

			if ( static::tokens_in_url() ) {
				// add tokens to the url
				$url = $this->tokeniseURL( $url, $request );
			}

			$ch = curl_init( $url );

			// data is always encrypted/encoded
			$data = $this->encrypt(
				$this->encode($request->toMap())
			);
			// set options with data and merged defaults
			$options = $this->curlOptions( $data );
			curl_setopt_array( $ch, $options );

			// add headers (unencrypted, salt is not being sent)
			$headers = [
				CURLOPT_HEADER => $this->curlHeaders( [
					self::RequestTokenHeader => $request->RequestToken,
				] ),
			];
			curl_setopt_array( $ch, $headers );

			// add encoded/encrypted headers
			$request->Headers = $this->encrypt( $this->encode( $headers ) );
			// data should already be encoded/encrypted
			$request->Data = $data;

			$request->Mode   = ( Director::isLive() ? 'live' : ( Director::isTest() ? 'test' : 'dev' ) );
			$request->Status = $request::StatusSending;

			$request->write();

			$request->RequestStart = microtime( true );

			$response = curl_exec( $ch );

			$request->RequestEnd = microtime( true );

			$resultCode = curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );

			if ( $response === false ) {
				$request->Status        = $request::StatusFailed;
				$request->Outcome       = $request::OutcomeFailure;

				$resultMessage = curl_error( $ch );
			} else {

				if ( ! fnmatch( '2??', $resultCode ) ) {
					// not a 200 response, fail
					$request->Status        = $request::StatusCompleted;
					$request->Outcome       = $request::OutcomeFailure;

					$resultMessage = curl_error( $ch ) ?: "Result Code $resultCode";

				} else {

					$contentType = curl_getinfo( $ch, CURLINFO_CONTENT_TYPE );
					if ( $contentType != $this->contentType() ) {
						$request->Status        = $request::StatusCompleted;
						$request->Outcome       = $request::OutcomeFailure;

						$resultMessage = "Bad content type '$contentType'";
					} else {

						$response = $this->decode(
							$this->decrypt( $response, '' ),
							$contentType
						);

					}
				}
			}

		} catch (Exception $e) {
			$resultCode = $e->getCode();
			$resultMessage = $e->getMessage();

		}
		$request->ResultCode = $resultCode;
		$request->ResultMessage = $resultMessage;
		$request->write();

		return $response ?: null;
	}

	/**
	 * Check if the raw result form a request is OK, i.e. for curl falsish is no, otherwise yes
	 * @param $result
	 *
	 * @return bool false if not, true if it is
	 */
	public function isOK($result) {
		return (bool)$result;
	}

	/**
	 * Build options for curl to use in curl_setopt_array. If data is provided then the options will specify
	 * a post request with data in the body, otherwise a GET request will be made.
	 *
	 * @param array $data    to send with request, should be pre-encoded/encrypted etc
	 * @param array $options additional options to merge into configured curl_options
	 *
	 * @return array
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 */
	protected function curlOptions( $data = [], $options = [] ) {
		$options = array_merge(
			static::config()->get( 'curl_options' ),
			$options
		);
		if ( $data ) {
			$options[ CURLOPT_POST ]       = true;
			$options[ CURLOPT_POSTFIELDS ] = $data;
		} else {
			$options[ CURLOPT_HTTPGET ] = true;
		}

		return $options;
	}

	/**
	 * Build headers to send with the request including auth token and site identifier unless config.tokens_in_url is specified
	 *
	 * @param array $headers additional headers, not an associative array but just values e.g.  'HeaderName: Value'
	 *
	 * @return array of headers, needs to be added to option with CURLOPT_HEADER key
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 */
	protected function curlHeaders( $headers = [] ) {
		$headers = array_merge(
			$this->config()->get( 'curl_headers' ) ?: [],
			[
				static::ContentTypeHeader => $this->contentType(),      // from encoder
				static::AcceptTypeHeader  => $this->acceptType(),       // from decoder
				static::UserAgentHeader   => $this->userAgent(),
			],
			$this->config()->get( 'tokens_in_url' )
				? []
				: [
				static::AuthTokenHeader      => $this->authToken(),
				static::SiteIdentifierHeader => $this->siteToken(),
			],
			$headers
		);
		// if we have any arrays then implode to a string header
		$headers = array_map(
			function ( $header ) {
				if ( is_array( $header ) ) {
					$header = implode( ': ', $header );
				}

				return $header;
			},
			$headers
		);

		return $headers;
	}

	/**
	 * Add tokens to URL
	 * @param                          $url
	 * @param \DelectusApiRequestModel $request
	 *
	 * @return string
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 */
	protected function tokeniseURL($url, DelectusApiRequestModel $request) {
		// pass auth and other tokens on query string

		if ( 'https' != strtolower( parse_url( $url, PHP_URL_SCHEME ) ) ) {
			if ( Director::isLive() ) {
				throw new Exception( "In live mode I Can't pass tokens in URL if not https url" );
			}
		}

		$url .= '?' . self::AuthTokenParameter . '=' . $this->authToken()
		        . '&' . self::SiteIdentifierParameter . '=' . $this->siteToken()
		        . '&' . self::RequestTokenParameter . '=' . $request->RequestToken;

		return $url;
	}

	/**
	 * Build a url from the endpoint for the action from config, the version, the site token and the action,
	 * e.g. 'https://api.delectus.io/<version>/add'
	 *
	 * @param string $endpoint one of the EndpointABC constants
	 * @param string $action   one of the ActionABC constants
	 *
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	protected function endpoint( $endpoint, $action ) {
		return Controller::join_links(
			$this->module->endpoint($endpoint),
			$this->module->version(),
			$action
		);
	}

	/**
	 * Return authentication token suitable for passing as an HTTP header or on a url,
	 * made from the ClientToken or config.client_token
	 *
	 * @return string
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 */
	protected function authToken() {
		return $this->encrypt(
			DelectusModule::client_token(),
			DelectusModule::client_salt()
		);
	}

	/**
	 * Return site identifier token suitable for passing as an HTTP header or on a url
	 *
	 * @return string
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 */
	protected function siteToken() {
		return $this->encrypt(
			DelectusModule::site_identifier(),
			DelectusModule::client_salt()
		);
	}

	protected function userAgent() {
		return _t(
			'Delectus.UserAgent',
			$this->config()->get( 'user_agent' ),
			[
				'version' => $this->module->version(),
				'module'  => $this->module->module_name(),
			]
		);
	}

	/**
	 * Package up some info about the item to send so when we call back from delectus we can identify it easily
	 *
	 * @param $item
	 *
	 * @return mixed
	 */
	public static function package_item_info( $item ) {
		if ( $item instanceof DataObject ) {
			$info = [
				'Type' => 'Model',
				'Data' => "$item->ClassName|$item->ID",
			];
		} elseif ( is_array( $item ) ) {
			$info = [
				'Type' => 'Array',
				'Data' => $item,
			];
		} elseif ( is_object( $item ) ) {
			$info = [
				'Type' => 'Object',
				'Data' => serialize( $item ),
			];
		} else {
			$info = [
				'Type' => 'Mixed',
				'Data' => $item,
			];
		}

		return $info;
	}

	public static function unpackage_item_info( $itemInfo ) {
		return $itemInfo['Data'];
	}

	/**
	 * Return client token from SiteConfig or this module config.
	 *
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public static function tokens_in_url() {
		static $tokensInURL;
		if ( is_null( $tokensInURL ) ) {
			$siteConfig  = SiteConfig::current_site_config()->{DelectusSiteConfigExtension::TokensInURLFieldName};
			$tokensInURL = is_null( $siteConfig )
				? static::config()->get( 'tokens_in_url' )
				: $siteConfig;

		}

		return $tokensInURL;
	}

}