<?php

/**
 * DelectusSearchModel used to record details of a search and pass terms and meta data to the search service.
 *
 * @property string Title
 * @property string Terms
 * @property string Hash
 * @property int    Counter
 * @property string SearchToken
 */
class DelectusSearchModel extends DelectusModel {
	const ModelTokenFieldName   = 'SearchToken';
	const RequestTokenFieldName = 'SearchToken';

	private static $db = [
		'Title'                   => 'Varchar(255)',
		'Terms'                   => 'Varchar(255)',
		self::ModelTokenFieldName => DelectusModule::TokenSchema,
		'Hash'                    => 'Varchar(64)',
		'Counter'                 => 'Int',
	];

	public function onBeforeWrite() {
		if (!$this->{self::ModelTokenFieldName}) {
			$this->{self::ModelTokenFieldName} = DelectusModule::generate_token();
		}
		if (!$this->Title) {
			$this->Title = $this->Terms;
		}
		$this->Hash = static::hash( $this->Terms );
		$this->Counter ++;

	}

	public static function hash( $terms ) {
		return md5( preg_replace( '/s+/', '', strtolower( $terms ) ) );
	}
}