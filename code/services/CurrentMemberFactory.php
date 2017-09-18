<?php

use SilverStripe\Framework\Injector\Factory;

class DelectusCurrentMemberFactory implements Factory {

	/**
	 * Creates a new service instance.
	 *
	 * @param string $service The class name of the service.
	 * @param array  $params  The constructor parameters.
	 *
	 * @return object The created service instances.
	 */
	public function create( $service, array $params = array() ) {
		return Member::currentUser();
	}
}