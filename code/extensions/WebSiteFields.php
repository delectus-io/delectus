<?php

/**
 * Add fields for a single defined website.
 */
class DelectusWebSiteFieldsExtension extends \DataExtension {
	const PrimaryDomainFieldName       = 'PrimaryDomain';
	const SiteIdentifierFieldName      = 'SiteIdentifier';
	const TokensInURLFieldName         = 'TokensInURL';
	const EncryptionAlgorythmFieldName = 'EncryptionAlgorythm';
	const LastRefreshedFieldName       = 'LastRefreshed';

	const RequestTokenFieldName = self::SiteIdentifierFieldName;
	const ModelTokenFieldName   = self::SiteIdentifierFieldName;

	private static $db = [
		self::PrimaryDomainFieldName       => 'Varchar(255)',
		self::ModelTokenFieldName          => 'Varchar(255)',
		self::EncryptionAlgorythmFieldName => 'Enum("aes-256-ctr,No Encryption")',
		self::TokensInURLFieldName         => 'Boolean',
		self::LastRefreshedFieldName       => 'SS_Datetime',
	];

}