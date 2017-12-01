<?php

/**
 * Link represents a link to a resource other than a File
 */
class DelectusLinkModel extends DataObject {
	private static $db = [
		'URL' => 'Text'
	];
	private static $has_one = [
		'WebSite' => DelectusWebSiteModel::class
	];

	public function EditLink() {
		return '/resource/link/' . $this->ID . '/edit';
	}

	public function DeleteLink() {
		return '/resource/link/' . $this->ID . '/delete';
	}

	public function onBeforeWrite() {
		parent::onBeforeWrite();
		if ( ! $this->owner->WebSiteID ) {
			if ( $owner = Injector::inst()->get( 'DelectusWebSitesOwner' ) ) {
				if ( ! $site = $owner->WebSites()->filter( 'IsDefault', 1 )->first() ) {
					$site = $owner->WebSites()->first();
				}
				if ( $site ) {
					$this->owner->WebSiteID = $site->ID;
				}
			}
		}
	}

}