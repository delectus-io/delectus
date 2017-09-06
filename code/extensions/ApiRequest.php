<?php

/**
 * DelectusApiRequestExtension adds fields and functions for models which represent
 * and Api Request to delectus or back to client site so are kept in sync between
 * backend client-side models.
 */
class DelectusApiRequestExtension extends DataExtension {
	/**
	 * Common fields for logging/making a request between delectus services
	 *
	 * @var array
	 */
	private static $db = [
		'RequestToken'    => 'Varchar(255)',
		'ClientToken'     => 'Varchar(255)',
		'SiteIdentifier'  => 'Varchar(255)',
		'Version'         => 'Varchar(8)',
		'Endpoint'        => 'Varchar(16)',
		'Environment'     => 'Enum("dev,test,live")',
		'Action'          => 'Varchar(16)',
		'Outcome'         => 'Enum("Waiting,Success,Failure")',
		'ResponseCode'    => 'Int',
		'ResponseMessage' => 'Text',
	];

}