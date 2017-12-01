<?php

/**
 * DelectusWebSitesOwnerExtension add to model which 'owns' websites, e.g. Member or Client
 * @method HasManyList WebSites()
 */
class DelectusWebSitesOwnerExtension extends DataExtension {
	private static $has_many = [
		'WebSites' => DelectusWebSiteModel::class,
	];
}