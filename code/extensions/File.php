<?php

class DelectusFileExtension extends DelectusModelExtension {
	/**
	 * Only add fields if we have an ID (so not the root assets/ folder).
	 * @return bool
	 */
	public function shouldAddDelectusInfoFields() {
		return (bool)$this->owner->ID;
	}

	/**
	 * @return bool
	 * @throws \InvalidArgumentException
	 */
	public function canView() {
		if (Permission::check('ADMIN')) {
//			return true;
		}
		return DelectusModule::resource_owner()->canViewFile($this->owner);
	}
}