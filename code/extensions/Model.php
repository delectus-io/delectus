<?php

/**
 * DelectusModelExtension provides fields for Model's which are indexed by delectus to make e.g. URL available in front-end
 *
 * @property string StatusFieldName
 * @property string LastUpdatedFieldName
 * @property string RemoteLinkFieldName
 * @property string ModelTokenFieldName
 */
class DelectusModelExtension extends DataExtension {
	const StatusFieldName      = 'DelectusStatus';
	const LastUpdatedFieldName = 'DelectusLastUpdated';
	const RemoteLinkFieldName  = 'DelectusRemoteLink';
	const ModelTokenFieldName  = 'DelectusModelToken';

	private static $db = [
		self::StatusFieldName      => 'Varchar(32)',
		self::LastUpdatedFieldName => 'SS_DateTime',
		self::RemoteLinkFieldName  => 'Text',
		self::ModelTokenFieldName  => 'Varchar(255)',
	];

	// set to false to disable Delectus functions at runtime, e.g. during testing other functionality
	private static $delectus_enabled = true;

	public function onBeforeWrite() {
		if ( $this->owner->isInDB() ) {
			if ( $this->owner->isChanged( self::StatusFieldName ) ) {
				$this->owner->{self::LastUpdatedFieldName} = null;
			}
		}
		if ( ! $this->owner->{self::ModelTokenFieldName} ) {
			$this->owner->{self::ModelTokenFieldName} = DelectusModule::generate_token();
		}
		parent::onBeforeWrite();
	}

	public function updateCMSFields( FieldList $fields ) {
		parent::updateCMSFields( $fields );

		if ( $this->shouldAddDelectusInfoFields() ) {
			$addFields = [
				new TextField(
					self::StatusFieldName,
					_t(
						'Delectus.StatusFieldName',
						"Status"
					)
				),
				new DatetimeField(
					self::LastUpdatedFieldName,
					_t(
						'Delectus.LastUpdatedFieldLabel',
						"Last Update"
					)
				),
				new TextField(
					self::RemoteLinkFieldName,
					_t(
						'Delectus.RemoteLinkFieldLabel',
						"Link"
					)
				),
				new TextField(
					self::ModelTokenFieldName,
					_t(
						'Delectus.ModelTokenFieldLabel',
						"Token"
					)
				),
			];
			if ( ! Permission::check( 'ADMIN' ) ) {
				foreach ( $addFields as $key => $field ) {
					$addFields[ $key ] = $field->performReadonlyTransformation();
				}
			}
			$fields->addFieldsToTab(
				DelectusModule::cms_tab_name(),
				$addFields
			);
		}
	}

	/**
	 * Sometimes we don't want to add the token field to a model, e.g. if we're in assets and at the root folder.
	 *
	 * @return bool
	 */
	protected function shouldAddDelectusInfoFields() {
		return true;
	}

}