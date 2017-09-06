<?php

use SilverStripe\Framework\Injector\Factory;

/**
 * DelectusCurrentMemberConfigFactory provides configuration information for delectus services by using the current members
 * DelectusConfig method added to the Model, e.g. one which returns the Member's Client which has the correct CientToken, SiteIdentifier etc
 * fields on it.
 */
class DelectusCurrentMemberConfigFactory implements Factory {

	/**
	 * Returns a config object for the current Member (they must have a 'DelectusConfig' accessor method which returns a model which has
	 * fields on it such as SiteIdentifier, ClientToken etc, e.g. by adding and extension with this method to Member class)
	 *
	 * @param string $service The class name of the service.
	 * @param array  $params  The constructor parameters.
	 *
	 * @return \ArrayData|\DataObject
	 */
	public function create( $service, array $params = array() ) {

		if ( $member = Member::currentUser() ) {
			$config = $member->DelectusConfig();
		} else {
			$config = new ArrayData( [
				'ClientToken'    => null,
				'SiteIdentifier' => null,
			] );
		}

		return $config;
	}
}