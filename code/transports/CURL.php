<?php

class DelectusCURLTransport extends \Object implements DelectusHTTPTransportInterface {
	const UserAgentString = 'Delectus Backend {version} {module}';
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

	/**
	 * Make request to a Delectus endpoint/action passing any data.
	 *
	 * If config.tokens_in_url are specified will also add 'at' param to querystring for the auth token.
	 *
	 * @param DelectusApiRequestModel $request incoming request parameters, updated to reflect results. written to database.
	 * @param array                   $data    optional data to add to request payload
	 *
	 * @return mixed
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 */
	public function makeRequest( DelectusApiRequestModel $request, $data = [] ) {
		$ch       = null;
		$response = null;

		// make sure request is fully initialised
		if ( ! $request->isInDB() ) {
			$request->write();
		}

		$url = static::endpoint( $request->Endpoint, $request->Action );

		$data    = array_merge(
			$request->toMap(),
			$data
		);
		$headers = [
			self::RequestTokenHeader => $request->RequestToken,
		];

		try {
			if ( DelectusModule::tokens_in_url() ) {
				// pass auth and other tokens on query string

				if ( 'https' != strtolower( parse_url( $url, PHP_URL_SCHEME ) ) ) {
					if ( Director::isLive() ) {
						throw new Exception( "In live mode I Can't pass tokens in URL if not https url" );
					}
				}

				$url .= '?' . self::AuthTokenParameter . '=' . static::auth_token()
				        . '&' . self::SiteIdentifierParameter . '=' . static::site_token()
				        . '&' . self::RequestTokenParameter . '=' . $request->RequestToken;
			}
			$ch = curl_init( $url );

			curl_setopt_array( $ch, $data = static::curl_options( $data ) );
			curl_setopt_array( $ch, $headers = static::curl_headers( $headers ) );

			if ( Director::isDev() ) {
				$request->Headers = json_encode( $headers );
				$request->Data    = json_encode( $data );
			} else {
				$request->Headers = static::encode_data( $headers );
				$request->Data    = static::encode_data( $data );
			}
			$request->Mode   = ( Director::isLive() ? 'live' : ( Director::isTest() ? 'test' : 'dev' ) );
			$request->Status = $request::StatusSending;

			$request->write();

			$request->RequestStartMS = microtime( true );

			$response = curl_exec( $ch );

			$request->RequestEndMS = microtime( true );

			if ( $response === false ) {
				$request->Status  = $request::StatusFailed;
				$request->Outcome = $request::OutcomeFailure;

				throw new Exception( "Error: " . curl_error( $ch ) );
			}
			$responseCode        = curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
			$request->ResultCode = $responseCode;

			if ( $responseCode != 200 ) {
				$request->Status  = $request::StatusCompleted;
				$request->Outcome = $request::OutcomeFailure;

				throw new Exception( "Failed response code: $responseCode", $responseCode );
			}
			$contentType = curl_getinfo( $ch, CURLINFO_CONTENT_TYPE );
			if ( $contentType != 'application/json' ) {
				$request->Status  = $request::StatusCompleted;
				$request->Outcome = $request::OutcomeFailure;

				throw new Exception( "Bad content type: $contentType", $responseCode );
			}
			$response = static::decode_data( $response, $contentType );

			$request->ResultMessage = isset( $response['message'] )
				? $response['message']
				: _t(
					'Delectus.UnknownErrorMessage',
					'Unknown Error'
				);

		} catch ( Exception $e ) {
			$request->ResultCode    = $e->getCode();
			$request->ResultMessage = $e->getMessage();
		} finally {
			if ( $ch ) {
				curl_close( $ch );
			}
		}
		$request->write();

		return $response;
	}

	/**
	 * Build options for curl to use in curl_setopt_array. If data is provided then the options will specify
	 * a post request with data encoded in the body, otherwise a GET request will be made.
	 *
	 * @param array $data    to send with request
	 * @param array $options additional options to merge into configured curl_options
	 *
	 * @return array
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 */
	protected static function curl_options( $data = [], $options = [] ) {
		$data = static::encode_data( $data );

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
	 * @return array with header strings keyed off CURLOPT_HEADER ready to merge into curl options
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 */
	protected static function curl_headers( $headers = [] ) {
		$headers = array_merge(
			static::config()->get( 'curl_headers' ) ?: [],
			[
				self::ContentTypeHeader . ': ' . self::ContentType,
				'User-Agent: ' . _t(
					'Delectus.UserAgentString',
					static::UserAgentString,
					[
						'version' => DelectusModule::version(),
						'module'  => DelectusModule::module_name(),
					]
				),
			],
			static::config()->get( 'tokens_in_url' )
				? []
				: [
				self::AuthTokenHeader . ': ' . static::auth_token(),
				self::SiteIdentifierHeader . ': ' . static::site_token(),
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

		return [
			CURLOPT_HEADER => $headers,
		];
	}

	/**
	 * Package up some info about the item to send so when we call back from delectus we can identify it easily
	 *
	 * @param $item
	 *
	 * @return mixed
	 */
	protected static function package_item_info( $item ) {
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

	protected function unpackage_item_info( $itemInfo ) {
		return $itemInfo['Data'];
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
	protected static function endpoint( $endpoint, $action ) {
		return Controller::join_links(
			static::config()->get( 'endpoints' )[ $endpoint ],
			static::version(),
			$action
		);
	}

	protected static function version() {
		return DelectusModule::version();
	}

	/**
	 * Return authentication token suitable for passing as an HTTP header or on a url,
	 * made from the ClientToken or config.client_token
	 *
	 * @return string
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 */
	protected static function auth_token() {
		return base64_encode(
			static::encrypt_data(
				DelectusModule::client_token(),
				DelectusModule::client_salt()
			)
		);
	}

	/**
	 * Return site identifier token suitable for passing as an HTTP header or on a url
	 *
	 * @return string
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 */
	protected static function site_token() {
		return base64_encode(
			static::encrypt_data(
				DelectusModule::site_identifier(),
				DelectusModule::client_salt()
			)
		);
	}

	/**
	 * Encrypt data using password, e.g. ClientSalt and then base64_encode it
	 *
	 * @param string $data must already converted to a string, e.g. via json_encode
	 * @param string $password
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function encrypt_data( $data, $password ) {
		if ( $algorythm = static::config()->get( 'encryption_algorythm' ) ) {
			$data = base64_encode( openssl_encrypt( $data, $algorythm, $password ) );
		}

		return DelectusModule::generate_token() . $data;
	}

	/**
	 * Decrypt data using password, e.g. ClientSalt after base64_decoding it first
	 *
	 * @param string $data
	 * @param string $password
	 *
	 * @return string
	 */
	public static function decrypt_data( $data, $password ) {
		if ( $algorythm = static::config()->get( 'encryption_algorythm' ) ) {
			$data = substr(
				openssl_decrypt(
					base64_decode( $data ),
					$algorythm,
					$password
				),
				DelectusModule::TokenLength
			);
		}

		return $data;
	}

	/**
	 * Encode data for transport, currently only json encoding is supported
	 *
	 * @param mixed  $data
	 * @param string $contentType not used at the moment
	 *
	 * @return string
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 */
	public static function encode_data( $data, $contentType = self::ContentType ) {
		return static::encrypt_data( json_encode( $data ), DelectusModule::client_salt() );
	}

	/**
	 * Decode data from transport, currently only json encoding is supported, may encrypt it
	 *
	 * @param mixed  $data
	 * @param string $contentType not used at the moment
	 *
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public static function decode_data( $data, $contentType = self::ContentType ) {
		return json_decode( static::decrypt_data( $data, DelectusModule::client_salt() ), true );
	}

}