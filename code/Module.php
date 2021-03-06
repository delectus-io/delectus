<?php

/**
 * Shared functionality for delectus service modules such as delectus-search and delectus-index.
 *
 * This will be required by composer.json and installed automatically when you install a module which needs it.
 */
class DelectusModule extends \Object {
	const ModuleName = 'delectus';

	// length of tokens generated by generate_token() and stored in the database
	const TokenLength = 64;
	const TokenSchema = 'Varchar(' . self::TokenLength . ')';

	// name of tab in cms to show delectus related fields/controls on
	private static $cms_tab_name = 'Root.Delectus';

	private static $admin_tab_name = 'Root.DelectusAdmin';

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
	 * Pass site and auth tokens on the url when making requests, e.g. if X-Client-Auth and X-Client-Site headers aren't being passed via proxy or some such
	 *
	 * @var bool
	 */
	private static $tokens_in_url = false;

	/**
	 * Default encryption algorythm
	 *
	 * @var string
	 */
	private static $default_encryption_algorythm = 'aes-256-ctr';

	/**
	 * Used to generate the upload folder for clients, '{' token '}' replacement is available for:
	 *
	 * -  <ConfigFieldName>   = name of a field from the config, e.g. 'ClientToken', 'S3Folder' if S3 extension installed etc
	 * -  ID                  = ID of model which holds config
	 * -  Name                = full name or Title of client, this may not be unique so need to use with e.g. ID or other unique value, this
	 *                          may also be exposed in the URL so be carefull there
	 *
	 * Tokens are optionally cleaned using url filter before replacement (see self.client_upload_folder)
	 *
	 * Should be relative to assets folder.
	 *
	 * @var string
	 */
	private static $delectus_resources_folder_format = 'clients/{DelectusClientToken}/resources';

	private static $delectus_private_folder_format = 'clients/{DelectusClientToken}/private';

	// max number of files which can be uploaded at once (via multiple file upload)
	private static $default_max_concurrent_files = 5;

	// max size of a single file uploaded, in MB
	private static $default_max_upload_file_size = 10;

	/**
	 * Return client token from SiteConfig or this module config.
	 *
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public static function tokens_in_url() {
		static $tokensInURL;
		if ( is_null( $tokensInURL ) ) {
			$siteConfig  = static::config_model()->{DelectusConfigFieldsExtension::TokensInURLFieldName};
			$tokensInURL = is_null( $siteConfig )
				? static::config()->get( 'tokens_in_url' )
				: $siteConfig;

		}

		return $tokensInURL;
	}

	/**
	 * Return the max size of a single uploaded file.
	 *
	 * @return int
	 */
	public static function default_max_upload_file_size() {
		return (int) static::config()->get( 'default_max_upload_file_size' );
	}

	/**
	 * Return the max number of files which can be uploaded at a time.
	 *
	 * @return int
	 */
	public static function default_max_concurrent_files() {
		return (int) static::config()->get( 'default_max_concurrent_files' );
	}

	/**
	 * @return \DelectusTransportInterface|\DelectusHTTPTransportInterface
	 */
	public static function transport() {
		return \Injector::inst()->get( 'DelectusTransport' );
	}

	/**
	 * @return \DelectusIndexServiceInterface
	 */
	public static function index_service() {
		return \Injector::inst()->get( 'DelectusIndexService' );
	}

	/**
	 * @return \DelectusSearchServiceInterface
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
	public static function encryption_algorythm( $configModel = null ) {
		static $algorythm;
		if ( is_null( $algorythm ) || $configModel ) {
			$configModel = $configModel ?: static::config_model();

			return $configModel->{DelectusConfigFieldsExtension::EncryptionAlgorythmFieldName}
				?: static::config()->get( 'default_encryption_algorythm' );
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

		return md5( $token );
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
			$clientToken = static::config_model()->{DelectusConfigFieldsExtension::ClientTokenFieldName}
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
			$salt = static::config_model()->{DelectusConfigFieldsExtension::ClientSaltFieldName}
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
			$secret = static::config_model()->{DelectusConfigFieldsExtension::ClientSecretFieldName}
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
			$siteID = static::config_model()->{DelectusConfigFieldsExtension::SiteIdentifierFieldName}
				?: static::config()->get( 'site_identifier' );
		}

		return $siteID;
	}

	/**
	 * Return instance of the model that has configuration such as ClientToken, SiteIdentified etc from Injector 'DelectusConfigModel'.
	 * by default this is the current SiteConfig.
	 *
	 * @return \ArrayData|\DataObject|\DelectusConfigFieldsExtension
	 */
	public static function config_model() {
		static $model;
		if ( is_null( $model ) ) {
			$model = Injector::inst()->create( 'DelectusConfigObject' );
		}

		return $model;
	}

	public static function max_upload_file_size() {
		return static::config_model()->{DelectusConfigFieldsExtension::MaxFileSizeFieldName}
			?: static::default_max_upload_file_size();
	}

	public static function max_concurrent_files() {
		return static::config_model()->{DelectusConfigFieldsExtension::MaxConcurrentFilesFieldName}
			?: static::default_max_concurrent_files();
	}

	/**
	 * Return folder for client files to upload to, either from the current config model, or build one using config.delectus_resources_folder_format. Should be
	 * used by dropzone, file listings etc delectus_resources_folder_format can be set on the extended model, or on this Module.
	 *
	 * @param null $configModel use this one instead of one from factory, e.g. if Member is being created then
	 *                          no current member exists, however maybe in the process of being created so
	 *                          has enough information to build the folder name
	 *
	 * @return \Folder
	 */
	public static function resources_folder( $configModel = null ) {
		static $folder;

		if ( is_null( $folder ) || $configModel ) {

			$configModel = $configModel ?: static::config_model();

			if ( $configModel ) {

				if ( $configModel->{DelectusConfigFieldsExtension::UploadFolderFieldName} ) {
					$folderPath = $configModel->{DelectusConfigFieldsExtension::UploadFolderFieldName};

				} else {

					$folderPath = Controller::join_links(
						str_replace(
							array_map(
								function ( $fieldName ) {
									return '{' . $fieldName . '}';
								},
								array_keys( $configModel->toMap() )
							),
							$configModel->toMap(),
							$configModel->config()->get( 'delectus_resources_folder_format' )
								?: static::config()->get( 'delectus_resources_folder_format' )
						)
					);
				}

				$folder = Folder::find_or_make( $folderPath );
			}
		}

		return $folder;
	}

	/**
	 * Return folder for client files to upload to, either from the current config model, or build one using config.delectus_resources_folder_format. Should be
	 * used by dropzone, file listings etc delectus_resources_folder_format can be set on the extended model, or on this Module.
	 *
	 * @param null $configModel use this one instead of one from factory, e.g. if Member is being created then
	 *                          no current member exists, however maybe in the process of being created so
	 *                          has enough information to build the folder name
	 *
	 * @return \Folder
	 */
	public static function private_folder( $configModel = null ) {
		static $folder;

		if ( is_null( $folder ) || $configModel ) {

			$configModel = $configModel ?: static::config_model();

			if ( $configModel ) {

				if ( $configModel->{DelectusConfigFieldsExtension::PrivateFolderFieldName} ) {
					$folderPath = $configModel->{DelectusConfigFieldsExtension::PrivateFolderFieldName};

				} else {

					$folderPath = Controller::join_links(
						str_replace(
							array_map(
								function ( $fieldName ) {
									return '{' . $fieldName . '}';
								},
								array_keys( $configModel->toMap() )
							),
							$configModel->toMap(),
							$configModel->config()->get( 'delectus_private_folder_format' )
								?: static::config()->get( 'delectus_private_folder_format' )
						)
					);
				}

				$folder = Folder::find_or_make( $folderPath );
			}
		}

		return $folder;
	}
	/**
	 * Return version number from config of this exact module (so uninherited)
	 *
	 * @return string
	 */
	public static function version() {
		return static::config()->get( 'version', Config::UNINHERITED );
	}

	public static function endpoint( $endpoint ) {
		$endpoints = static::endpoints();
		if ( isset( $endpoints[ $endpoint ] ) ) {
			return $endpoints[ $endpoint ];
		}

		return null;
	}

	public static function endpoints() {
		return static::config()->get( 'endpoints', Config::UNINHERITED );
	}

	public static function module_name() {
		return static::ModuleName;
	}

}