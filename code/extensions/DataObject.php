<?php

/**
 * DelectusDataObjectExtension provides shared Fields, config and functions for extension to be added to models to provide index and search functionality,
 * for example DelectusFileExtension and DelectusPageExtension.
 */
abstract class DelectusDataObjectExtension extends DataExtension {
	const StatusFieldName      = 'DelectusStatus';
	const UpdatedDateFieldName = 'DelectusLastUpdated';
	const RemoteLinkFieldName  = 'DelectusRemoteLink';
	const ModelTokenFieldName  = 'DelectusModelToken';

	private static $db = [
		self::StatusFieldName      => 'Varchar(32)',
		self::UpdatedDateFieldName => 'SS_DateTime',
		self::RemoteLinkFieldName  => 'Text',
		self::ModelTokenFieldName  => 'Varchar(255)',
	];

	// set to false to disable Delectus functions at runtime, e.g. during testing other functionality
	private static $delectus_enabled = true;

	public function onBeforeWrite() {
		if ( $this->owner->isInDB() ) {
			if ( $this->owner->isChanged( self::StatusFieldName ) ) {
				$this->owner->{self::UpdatedDateFieldName} = date( 'Y-m-d H:i:s' );
			}
			$this->owner->{self::ModelTokenFieldName} = DelectusModule::generate_token();
		}
	}

	public function updateCMSFields( FieldList $fields ) {
		parent::updateCMSFields( $fields );
		$fields->addFieldToTab(
			DelectusModule::cms_tab_name(),
			$field = new TextField(
				static::ModelTokenFieldName,
				_t(
					'Delectus.ModelTokenLabel',
					"Delectus Token"
				)
			)
		);
		if ( ! Permission::check( 'ADMIN' ) ) {
			$field->performReadonlyTransformation();
		}
	}

	/**
	 * Set and/or get the current enabled state of this extension.
	 *
	 * @param null|bool $enable if passed then use it to set the enabled state of this extension
	 *
	 * @return bool if enable parameter was passed this will be the previous value otherwise the current value
	 */
	public function enabled( $enable = null ) {
		if ( func_num_args() ) {
			$return = \Config::inst()->get( static::class, 'delectus_enabled' );
			\Config::inst()->update( static::class, 'delectus_enabled', $enable );
		} else {
			$return = \Config::inst()->get( static::class, 'delectus_enabled' )
				&& $this->owner->config()->get( 'delectus_enabled', Config::UNINHERITED ) ;
		}

		return (bool) $return;
	}
}