<?php

class DelectusWebSiteStatisticModel extends DelectusModel {
	private static $db = [
		'PeriodStart' => 'SS_Datetime',
		'PeriodEnd'   => 'SS_Datetime',
		'Measure'     => 'Enum("Download")',
		'Unit'        => 'Enum("Counter")',
		'Measurement' => 'Varchar(255)',            // value of measurement, e.g. the counter value
		'ItemClass'   => 'Enum("' . File::class . ',' . DelectusLinkModel::class . '")',
		'ItemID'      => 'Text',                    // e.g. FileID
	];
	private static $has_one = [
		'WebSite' => DelectusWebSiteModel::class,
	];
}