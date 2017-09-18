<?php

/**
 * DelectusWebSiteOwnerExtension add to model which 'owns' websites, e.g. Member or Client
 * @method HasManyList WebSites()
 */
class DelectusWebSiteOwnerExtension extends DataExtension {
	private static $has_many = [
		'WebSites' => DelectusWebSiteModel::class,
	];
}