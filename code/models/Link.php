<?php

/**
 * Link represents a link to a resource other than a File
 */
class DelectusLinkModel extends DataObject {
	private static $db = [
		'URL' => 'Text'
	];

	public function EditLink() {
		return '/resource/Links/' . $this->ID . '/edit';
	}

	public function DeleteLink() {
		return '/resource/Links/' . $this->ID . '/delete';
	}
}