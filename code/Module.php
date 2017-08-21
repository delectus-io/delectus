<?php

/**
 * Shared functionality for delectus service modules such as delectus-search and delectus-index.
 *
 * This will be required by composer.json and installed automatically when you install a module which needs it.
 */
class DelectusModule extends \Object {
	// length of tokens generated by generate_token() and stored in the database
	const TokenLength = 64;
	const TokenSchema = 'Varchar(' . self::TokenLength . ')';

	// name of tab in cms to show delectus related fields/controls on
	private static $cms_tab_name = 'Root.Delectus';

	private static $admin_tab_name = 'Root.Admin';

	/**
	 * Set to the client token you have been allocated, used to communicate with the delectus service
	 *
	 * @var string
	 */
	private static $client_token = '';

	/**
	 * Set to the client secret you have been assigned, used to secure information sent to the delectus service.
	 *
	 * @var string
	 */
	private static $client_salt = '';

	/**
	 * Set to the site id for the current site, used to communicate with the delectus service
	 *
	 * @var string
	 */
	private static $site_identifier = '';

	/**
	 * Endpoints for this server in form of https://api.delectus.io/
	 *
	 * @var array
	 */
	private static $endpoints = [
		#   'index' => https://api.delectus.io/
	];
	/**
	 * API version this module targets.
	 *
	 * @var string
	 */
	private static $version = 'v1';

	/**
	 * @return \DelectusTransportInterface|\DelectusHTTPTransportInterface
	 */
	public static function transport() {
		return \Injector::inst()->get('DelectusTransport');
	}

	/**
	 * @return \DelectusIndexService
	 */
	public static function index_service() {
		return \Injector::inst()->get( 'DelectusIndexService' );
	}

	/**
	 * @return DelectusSearchService
	 */
	public static function search_service() {
		return \Injector::inst()->get( 'DelectusSearchService' );
	}

	public static function cms_tab_name() {
		return static::config()->get( 'cms_tab_name' );
	}

	public static function admin_tab_name() {
		return static::config()->get( 'admin_tab_name' );
	}

	/**
	 * return the encryption algorythm configured in SiteConfig or null
	 *
	 * @return string
	 */
	public static function encryption_algorythm() {
		static $algorythm;
		if ( is_null( $algorythm ) ) {
			$algorythm = SiteConfig::current_site_config()->{DelectusSiteConfigExtension::EncryptionAlgorythmFieldName};
		}
		return $algorythm;
	}

	/**
	 * Generate a random string 64 characters long, not usefull for encrypting/decrypting things but as tokens etc.
	 *
	 * @param string $salt
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function generate_token( $salt = '' ) {
		$token = openssl_random_pseudo_bytes( static::TokenLength, $strong );
		if ( $salt && ! $strong ) {
			throw new Exception( "salt provided but not strong " . static::config()->get( 'encryption_algorythm' ) );
		}

		return md5($token);
	}

	/**
	 * Return client token from SiteConfig or this module config.
	 *
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public static function client_token() {
		static $clientToken;
		if ( is_null( $clientToken ) ) {
			$clientToken = SiteConfig::current_site_config()->{DelectusSiteConfigExtension::ClientTokenFieldName}
				?: static::config()->get( 'client_token' );

		}

		return $clientToken;
	}

	/**
	 * Return client salt from SiteConfig or this module config.
	 *
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public static function client_salt() {
		static $salt;
		if ( is_null( $salt ) ) {
			$salt = SiteConfig::current_site_config()->{DelectusSiteConfigExtension::ClientSaltFieldName}
				?: static::config()->get( 'client_salt' );
		}

		return $salt;
	}

	/**
	 * Return client salt from SiteConfig or this module config.
	 *
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public static function client_secret() {
		static $secret;
		if ( is_null( $secret ) ) {
			$secret = SiteConfig::current_site_config()->{DelectusSiteConfigExtension::ClientSecretFieldName}
				?: static::config()->get( 'client_secret' );
		}

		return $secret;
	}

	/**
	 * Return site identifier from SiteConfig or this module config.
	 *
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public static function site_identifier() {
		static $siteID;

		if ( is_null( $siteID ) ) {
			$siteID = SiteConfig::current_site_config()->{DelectusSiteConfigExtension::SiteIdentifierFieldName}
				?: static::config()->get( 'site_identifier' );
		}

		return $siteID;
	}

	/**
	 * Return version number from config.
	 *
	 * @return string
	 */
	public static function version() {
		return static::config()->get( 'version' );
	}

	public static function module_name() {
		return static::config()->get( 'module_name' );
	}

}