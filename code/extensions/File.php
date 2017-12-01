<?php

class DelectusFileExtension extends DelectusModelExtension {
	private static $has_one = [
		'WebSite' => DelectusWebSiteModel::class
	];
	/**
	 * Only add fields if we have an ID (so not the root assets/ folder).
	 * @return bool
	 */
	public function shouldAddDelectusInfoFields() {
		return (bool)$this->owner->ID;
	}

	public function onBeforeWrite() {
		parent::onBeforeWrite();
		if (!$this->owner->WebSiteID) {
			if ($owner = Injector::inst()->get('DelectusWebSitesOwner')) {
				if (!$site = $owner->WebSites()->filter('IsDefault', 1)->first()) {
					$site = $owner->WebSites()->first();
				}
				if ($site) {
					$this->owner->WebSiteID = $site->ID;
				}
			}
		}
	}

}