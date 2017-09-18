<?php

class DelectusFileExtension extends DelectusModelExtension {
	/**
	 * Only add fields if we have an ID (so not the root assets/ folder).
	 * @return bool
	 */
	public function shouldAddDelectusInfoFields() {
		return (bool)$this->owner->ID;
	}

}