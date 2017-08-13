<?php

/**
 * DelectusApiRequestExtension adds fields and functions for models which represent
 * and Api Request to delectus or back to client site so are kept in sync between
 * backend client-side models.
 */
class DelectusApiRequestExtension extends DataExtension {

	private static $db = [
		'RequestToken'    => 'Varchar(255)',
		'ClientToken'     => 'Varchar(255)',
		'SiteIdentifier'  => 'Varchar(255)',
		'Version'         => 'Varchar(8)',
		'ModelClass'      => 'Varchar(255)',
		'ModelID'         => 'Int',
		'ModelToken'      => 'Varchar(255)',
		'Endpoint'        => 'Varchar(16)',
		'Action'          => 'Varchar(16)',
		'ResponseCode'    => 'Int',
		'ResponseMessage' => 'Text',
	];

}