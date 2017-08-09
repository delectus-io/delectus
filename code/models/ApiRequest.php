<?php

/**
 * DelectusApiRequest model records requests made to delectus services and is updated by
 * a callback from delectus when the request has been processed.
 *
 * @property string Title
 * @property string RequestToken
 * @property string SiteIdentifier
 * @property string RequestURL
 * @property string Version
 * @property string ModelClass
 * @property int    ModelID
 * @property string ModelToken
 * @property int    MemberID
 * @property string ClientToken
 * @property string Status
 * @property string Outcome
 * @property string Endpoint
 * @property string Action
 * @property int    ResultCode
 * @property string ResultMessage
 * @property string Data
 * @property int    JobID
 * @property string RequestDuration
 * @property string LastStatusDate
 *
 * @method Member Member()
 */
class DelectusApiRequest extends DataObject {
	const StatusQueued    = 'Queued';
	const StatusSending   = 'Sending';
	const StatusSent      = 'Sent';
	const StatusFailed    = 'Failed';
	const StatusCompleted = 'Completed';

	const OutcomeWaiting = 'Waiting';
	const OutcomeSuccess = 'Success';
	const OutcomeFailure = 'Failure';

	const RequestTokenKey = 'RequestToken';

	// fields are added by DelectusApiRequestExtension
	private static $db = [
		'Status'          => 'Enum("Queued,Sending,Sent,Failed,Completed")',
		'Outcome'         => 'Enum("Undetermined,Determining,Success,Failure")',
		'LastStatusDate'  => 'SS_DateTime',
		'RequestDuration' => 'Int',
	];

	private static $has_one = [
		'Member' => 'Member',
	];

	private static $summary_fields = [
		'Title'         => 'Description',
		'Model.Title'   => 'Model Title',
		'Model.Link'    => 'Model Link',
		'ModelToken'    => 'Model Token',
		'Status'        => 'Status',
		'Outcome'       => 'Outcome',
		'ResultCode'    => 'Result Code',
		'ResultMessage' => 'Result Message',
	];

	/**
	 * Return the model from ModelClass and ModelID or null if can't or it doesn't exist in database (anymore)
	 *
	 * @return \DataObject
	 */
	public function getModel() {
		$model = null;
		if ( $this->ModelClass && $this->ModelID ) {
			$modelClass = $this->ModelClass;
			$model      = $modelClass::get()->byID( $this->ModelID );
		}

		return ( $model && $model->exists() ) ? $model : null;
	}

	public function setModel( $model ) {
		$this->ModelClass = $model->ClassName;
		$this->ModelID    = $model->ID;
		$this->ModelToken = $model->{DelectusDataObjectExtension::ModelTokenFieldName};
	}

	public function onBeforeWrite() {
		if ( ! $this->isInDB() ) {
			$this->RequestToken   = DelectusModule::generate_token();
			$this->ClientToken    = DelectusModule::client_token();
			$this->SiteIdentifier = DelectusModule::site_identifier();
			$this->MemberID       = Member::currentUserID();
		}
	}
}