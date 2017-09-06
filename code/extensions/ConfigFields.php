<?php

/**
 * DelectusConfigFields adds fields to a Model so it can be used to store information used for calling delectus
 * services, such as 'ClientToken', 'SiteIdentifier' etc
 */
class DelectusConfigFieldsExtension extends \DataExtension {
	const ClientTokenFieldName         = 'DelectusClientToken';
	const ClientSaltFieldName          = 'DelectusClientSalt';
	const ClientSecretFieldName        = 'DelectusClientSecret';
	const SiteIdentifierFieldName      = 'DelectusSiteIdentifier';
	const TokensInURLFieldName         = 'DelectusTokensInURL';
	const EncryptionAlgorythmFieldName = 'DelectusEncryptionAlgorythm';

	private static $db = [
		self::ClientTokenFieldName         => 'Varchar(255)',
		self::ClientSaltFieldName          => 'Varchar(255)',
		self::ClientSecretFieldName        => 'Varchar(255)',
		self::SiteIdentifierFieldName      => 'Varchar(255)',
		self::EncryptionAlgorythmFieldName => 'Varchar(255)',
		self::TokensInURLFieldName         => 'Boolean',
	];

	// hide fields on the extended model if false, show them read-only if true, unless ADMIN in which case they will be visible and editable
	private static $delectus_fields_visible = false;

	/**
	 * Called by Injector.DelectusConfigModel factory to get the relevant fields from the extended SiteConfig for Delectus services
	 * (e.g. ClientToken, SiteIdentifier etc)
	 */
	public function DelectusConfig() {
		return new ArrayData( [
			'ClientToken'         => $this->owner->{self::ClientTokenFieldName},
			'ClientSalt'          => $this->owner->{self::ClientSaltFieldName},
			'ClientSecret'        => $this->owner->{self::ClientSecretFieldName},
			'SiteIdentifier'      => $this->owner->{self::SiteIdentifierFieldName},
			'TokensInURL'         => $this->owner->{self::TokensInURLFieldName},
			'EncryptionAlgorythm' => $this->owner->{self::EncryptionAlgorythmFieldName},
		] );
	}

	/**
	 * Set some defaults for tokens and Title, requires that the model has its Member reference set for a sensible title,
	 * otherwise will use decorated current date for title.
	 *
	 * @throws \Exception
	 */
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		if ( ! $this->owner->isInDB() ) {
			if ( ! $this->{self::ClientSaltFieldName} ) {
				$this->{self::ClientSaltFieldName} = \DelectusModule::generate_token();
			}
			if ( ! $this->{self::ClientTokenFieldName} ) {
				$this->{self::ClientTokenFieldName} = \DelectusModule::generate_token();
			}
			if ( ! $this->{self::ClientSecretFieldName} ) {
				$this->{self::ClientSecretFieldName} = \DelectusModule::generate_token();
			}
			if ( ! $this->{self::EncryptionAlgorythmFieldName} ) {
				$this->{self::EncryptionAlgorythmFieldName} = \DelectusModule::encryption_algorythm();
			}
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
				DropdownField::create(
					self::EncryptionAlgorythmFieldName,
					_t(
						'Delectus.EncryptionAlgorythmLabel',
						'Request Data Encryption Method'
					),
					DelectusModule::encryption_algorythm() )
					->setRightTitle( _t( 'Delectus.EncryptionAlgorythmDescription', "How to encrypt data in requests, only choose No Encryption if over ssl or local testing!" ) )
					->setEmptyString( 'No encryption (not advised)' ),

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