<?php

use SilverStripe\Framework\Injector\Factory;

/**
 * DelectusCurrentMemberResourceOwner factory class if the current logged in memebr is the
 * owner of resources. Requires that Member has an extension which provides 'Resources' relationship.
 */
class DelectusCurrentMemberResourceOwner implements Factory {
	public function create( $service, array $params = array() ) {
		return Member::currentUser();
	}
}