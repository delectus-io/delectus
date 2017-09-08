<?php

use SilverStripe\Framework\Injector\Factory;

/**
 * DelectusCurrentMemberConfigFactory provides configuration information for delectus services by using the SiteConfig
 * DelectusConfig method added by DelectusSiteConfigExtension.
 */
class DelectusCurrentSiteConfigFactory implements Factory {

	/**
	 * Creates a new service instance.
	 *
	 * @param string $service The class name of the service.
	 * @param array  $params  The constructor parameters.
	 *
	 * @return \SiteConfig
	 */
	public function create( $service, array $params = array() ) {
		return SiteConfig::current_site_config();
	}
}