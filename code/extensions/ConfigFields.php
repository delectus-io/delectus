<?php

/**
 * DelectusConfigFields adds fields to a Model so it can be used to store information used for calling delectus
 * services, such as 'ClientToken', 'SiteIdentifier' etc. By default delectus adds this to SiteConfig via
 * Injector.DelectusConfigModel setting which points to the DelectusCurrentSiteConfigFactory class, however could be the
 * Member e.g. by setting the factory for DelectusConfigModel to DelectusCurrentMemberConfigFactory instead in app config.
 */
class DelectusConfigFieldsExtension extends \DataExtension {
	const ClientTokenFieldName         = 'DelectusClientToken';
	const ClientSaltFieldName          = 'DelectusClientSalt';
	const ClientSecretFieldName        = 'DelectusClientSecret';
	const SiteIdentifierFieldName      = 'DelectusSiteIdentifier';
	const TokensInURLFieldName         = 'DelectusTokensInURL';
	const EncryptionAlgorythmFieldName = 'DelectusEncryptionAlgorythm';
	const UploadFolderFieldName        = 'DelectusUploadFolder';
	const MaxConcurrentFilesFieldName  = 'DelectusMaxConcurrentFiles';
	const MaxFileSizeFieldName         = 'DelectusMaxFileSizeMB';

	private static $db = [
		self::ClientTokenFieldName         => 'Varchar(255)',
		self::ClientSaltFieldName          => 'Varchar(255)',
		self::ClientSecretFieldName        => 'Varchar(255)',
		self::SiteIdentifierFieldName      => 'Varchar(255)',
		self::EncryptionAlgorythmFieldName => 'Varchar(255)',
		self::TokensInURLFieldName         => 'Boolean',
		self::UploadFolderFieldName        => 'Varchar(255)',
		self::MaxConcurrentFilesFieldName  => 'Int',
		self::MaxFileSizeFieldName         => 'Int',
	];
	/**
	 * Name of fields which should be returned from the extended model as config fields e.g. by DelectusModule::config_model().
	 *
	 * Other extensions may add to this, e.g. S3Storage extension may add 'S3URN' and S3ApiKey and S3ApiSecret fields to here and the model.
	 *
	 * @var array
	 */
	private static $delectus_config_fields = [
		self::ClientTokenFieldName,
		self::ClientSaltFieldName,
		self::ClientSecretFieldName,
		self::SiteIdentifierFieldName,
		self::EncryptionAlgorythmFieldName,
		self::TokensInURLFieldName,
		self::UploadFolderFieldName,
		self::MaxConcurrentFilesFieldName,
		self::MaxFileSizeFieldName
	];

	// hide fields on the extended model if false, show them read-only if true, unless ADMIN in which case they will be visible and editable
	private static $delectus_fields_visible = false;

	/**
	 * Called by Injector.DelectusConfigModel factory to get the relevant fields from the extended SiteConfig for Delectus services
	 * (e.g. ClientToken, SiteIdentifier etc)
	 */
	public function DelectusConfig() {
		return new ArrayData(
			array_intersect_key(
				array_flip( $this->owner->config()->get( 'delectus_config_fields' ) ),
				$this->owner->toMap()
			)
		);
	}

	/**
	 * Set some defaults for tokens and Title, requires that the model has its Member reference set for a sensible title,
	 * otherwise will use decorated current date for title.
	 *
	 * @throws \Exception
	 */
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		if ( ! $this->owner->{self::ClientSaltFieldName} ) {
			$this->owner->{self::ClientSaltFieldName} = \DelectusModule::generate_token();
		}
		if ( ! $this->owner->{self::ClientTokenFieldName} ) {
			$this->owner->{self::ClientTokenFieldName} = \DelectusModule::generate_token();
		}
		if ( ! $this->owner->{self::ClientSecretFieldName} ) {
			$this->owner->{self::ClientSecretFieldName} = \DelectusModule::generate_token();
		}
		if ( ! $this->owner->{self::SiteIdentifierFieldName} ) {
			$this->owner->{self::SiteIdentifierFieldName} = \DelectusModule::generate_token();
		}
		if ( ! $this->owner->{self::EncryptionAlgorythmFieldName} ) {
			$this->owner->{self::EncryptionAlgorythmFieldName} = \DelectusModule::encryption_algorythm($this->owner);
		}
		if ( ! $this->owner->{self::UploadFolderFieldName} ) {
			$this->owner->{self::UploadFolderFieldName} = \DelectusModule::upload_folder( $this->owner)->Filename;
		}
		if ( ! $this->owner->{self::MaxFileSizeFieldName} ) {
			$this->owner->{self::MaxFileSizeFieldName} = \DelectusModule::default_max_upload_file_size();
		}
		if ( ! $this->owner->{self::MaxConcurrentFilesFieldName} ) {
			$this->owner->{self::MaxConcurrentFilesFieldName} = \DelectusModule::default_max_concurrent_files();
		}
	}

	public function updateCMSFields( FieldList $fields ) {
		if ( $this->owner->config()->get( 'delectus_fields_visible' ) || Permission::check( 'ADMIN' ) ) {
			$addFields = [
				TextField::create(
					self::ClientTokenFieldName,
					_t(
						'Delectus.ClientTokenLabel',
						"Client Token"
					),
					DelectusModule::client_token() )
					->setRightTitle( _t( 'Delectus.ClientTokenDescription', "Enter the client token from your Delectus client account, or set in config files" ) )
					->setAttribute( 'placeholder', "e.g. " . DelectusModule::generate_token() ),
				TextField::create(
					self::ClientSaltFieldName,
					_t(
						'Delectus.ClientSaltLabel',
						"Client Salt"
					),
					DelectusModule::client_salt() )
					->setRightTitle( _t( 'Delectus.ClientSaltDescription', "Enter the client salt from your Delectus client account, or set in config files" ) )
					->setAttribute( 'placeholder', "e.g. " . DelectusModule::generate_token() ),
				TextField::create(
					self::ClientSecretFieldName,
					_t(
						'Delectus.ClientSecretLabel',
						"Client Secret"
					),
					DelectusModule::client_secret() )
					->setRightTitle( _t( 'Delectus.ClientSecretDescription', "Enter the client secret from your Delectus client account, or set in config files" ) )
					->setAttribute( 'placeholder', "e.g. " . DelectusModule::generate_token() ),
				TextField::create(
					self::SiteIdentifierFieldName,
					_t(
						'Delectus.SiteIdentifierLabel',
						"Site Identifier"
					),
					DelectusModule::site_identifier() )
					->setRightTitle( _t( 'Delectus.SiteIdentifierDescription', "Enter the site identifier from your Delectus record for this website, or configure in SilverStripe" ) )
					->setAttribute( 'placeholder', "e.g. " . DelectusModule::generate_token() ),
				CheckboxField::create(
					self::TokensInURLFieldName,
					_t(
						'Delectus.TokensInURLLabel',
						'Request Tokens in URL'
					),
					DelectusModule::tokens_in_url() )
					->setRightTitle( _t( 'Delectus.TokensInURLDescription', "Send tokens on URL instead of headers, usefull if a proxy is preventing headers from getting through" ) ),
				TextField::create(
					self::EncryptionAlgorythmFieldName,
					_t(
						'Delectus.EncryptionAlgorythmLabel',
						'Request Data Encryption Method'
					),
					DelectusModule::encryption_algorythm() )
					->setRightTitle( _t(
						'Delectus.EncryptionAlgorythmDescription',
						"How to encrypt data in requests, only choose No Encryption if over ssl or local testing!"
					) ),
				TextField::create(
					self::UploadFolderFieldName,
					_t(
						'Delectus.UploadFolderLabel',
						'Upload folder name (relative to site base)'
					),
					DelectusModule::upload_folder()->Filename )
					->setRightTitle( _t(
						'Delectus.UploadFolderDescription',
						"Name of folder files get uploaded to via CMS and front-end. If this changes then existing files may be lost!"
					) ),
				TextField::create(
					self::MaxConcurrentFilesFieldName,
					_t(
						'Delectus.MaxConcurrentFilesLabel',
						'Max concurrent files'
					),
					DelectusModule::max_concurrent_files() )
					->setRightTitle( _t(
						'Delectus.UploadMaxConcurrentFilesDescription',
						"Max number of files which can be uploaded at a time, e.g. via drag-and-drop"
					) ),
				TextField::create(
					self::MaxFileSizeFieldName,
					_t(
						'Delectus.MaxFileSizeLabel',
						'Max file size'
					),
					DelectusModule::max_upload_file_size() )
					->setRightTitle( _t(
						'Delectus.MaxFileSizeDescription',
						"Max size of a single uploaded file"
					) ),
			];
			/** @var \FormField $field */
			foreach ( $addFields as $key => $field ) {
				if ( ! Permission::check( 'ADMIN' ) ) {
					$addFields[ $key ] = $field->performReadonlyTransformation();
				}
			}
			$fields->addFieldsToTab(
				DelectusModule::admin_tab_name(),
				$addFields
			);
		}

	}

}