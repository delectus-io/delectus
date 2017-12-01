<?php

/**
 * DelectusWebSiteModel
 *
 * @package Delectus
 *
 * @property int    RefreshStrategyID
 * @property string LastRefreshed
 * @method DataList Resources()
 *
 * @property string PrimaryDomain
 * @method DataList DomainNames()
 *
 * @property string SiteToken
 */
class DelectusWebSiteModel extends DelectusModel {
	const RequestTokenFieldName = DelectusWebSiteFieldsExtension::SiteIdentifierFieldName;
	const ModelTokenFieldName   = DelectusWebSiteFieldsExtension::SiteIdentifierFieldName;

	private static $db = [
		'Title' => 'Varchar(32)'
	];

	private static $has_many = [
		'Statistics' => DelectusWebSiteStatisticModel::class,
	];

	private static $singular_name = 'Web Site';
	private static $plural_name = 'Web Sites';

	public function canView( $member = null ) {
		return Member::currentUser()->WebSites()->filter('ID', $this->owner);
	}

	public function canEdit( $member = null ) {
		return Member::currentUser()->WebSites()->filter( 'ID', $this->owner );
	}

	public function canDelete( $member = null ) {
		return Member::currentUser()->WebSites()->filter( 'ID', $this->owner );
	}

	public static function get_for_owner() {
		return \Injector::inst()->get('DelectusWebSitesOwner')->WebSites();
	}

	/**
	 * Generate an SiteToken if not one set, create a local cache folder for the site if one doesn't exist.
	 *
	 * @throws \Exception
	 */
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		if (!$this->{DelectusWebSiteFieldsExtension::SiteIdentifierFieldName}) {
			$this->{DelectusWebSiteFieldsExtension::SiteIdentifierFieldName} = \DelectusModule::generate_token();
		}
		$attempts = 0;
		do {
			$exists = DelectusWebSiteModel::get()
				->exclude( 'ID', $this->ID )
				->filter( [
					DelectusWebSiteFieldsExtension::SiteIdentifierFieldName => $this->{DelectusWebSiteFieldsExtension::SiteIdentifierFieldName}
				])->count() > 0;

			if ($exists) {
				if ( ++ $attempts > 10 ) {
					throw new Exception( "Failed to generate a Unique SiteToken" );
				}
				$this->{DelectusWebSiteFieldsExtension::SiteIdentifierFieldName} = \DelectusModule::generate_token();
			}

		} while ($exists);
	}

}