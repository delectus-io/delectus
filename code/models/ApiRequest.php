<?php

use DelectusException as Exception;

/**
 * DelectusApiRequestModel model records requests made to delectus services and is updated by
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
 * @property string Headers             - in dev mode this will have json encoded version of data, encrypted in live mode
 * @property string Data                - in dev mode this will have json encoded version of data, encrypted in live mode
 * @property int    JobID
 * @property string RequestDate
 * @property number RequestStartMS
 * @property number RequestEndMS
 * @property string LastStatusDate
 * @property string Mode                - mode request made in, e.g. 'dev', 'test', 'live'
 *
 * @method Member Member()
 */
class DelectusApiRequestModel extends DelectusModel {
	const StatusQueued    = 'Queued';
	const StatusSending   = 'Sending';
	const StatusSent      = 'Sent';
	const StatusFailed    = 'Failed';
	const StatusCompleted = 'Completed';

	const OutcomeWaiting = 'Waiting';
	const OutcomeSuccess = 'Success';
	const OutcomeFailure = 'Failure';

	// name of the token for finding this model in a request
	const RequestTokenFieldName = 'RequestToken';

	private static $extensions = [
		DelectusApiRequestExtension::class,
	];

	// other fields are added by DelectusApiRequestExtension
	private static $db = [
		'Status'         => 'Enum("Queued,Sending,Sent,Failed,Completed")',
		'Outcome'        => 'Enum("Undetermined,Determining,Success,Failure")',
		'RunEpoch'       => 'Int',              // time() when the request should be satisfied
		'LastStatusDate' => 'SS_DateTime',
		'RequestDate'    => 'SS_DateTime',
		'RequestStartMS' => 'Decimal(10,3)',
		'RequestEndMS'   => 'Decimal(10,3)',
		'Headers'        => 'Text',
		'Data'           => 'Text',
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
	 * @throws \Delectus\Exceptions\Exception
	 */
	public function getModel() {
		$model = null;
		if ( ! $this->ModelID ) {
			throw new Exception( "No ModelID" );
		}
		if ( ! $this->ModelClass ) {
			throw new Exception( "No ModelClass" );
		}
		if ( ! ClassInfo::exists( $this->ModelClass ) ) {
			throw new Exception( "Bad model class '$this->ModelClass'" );
		}
		$modelClass = $this->ModelClass;
		$model      = $modelClass::get()->byID( $this->ModelID );

		return ( $model && $model->exists() ) ? $model : null;
	}

	public function setModel( $model ) {
		$this->ModelClass = $model->ClassName;
		$this->ModelID    = $model->ID;
		$this->ModelToken = $model->{DelectusDataObjectExtension::ModelTokenFieldName};
	}

	public function onBeforeWrite() {
		if ( ! $this->isInDB() ) {
			$this->RequestDate    = date( 'Y-m-d H:i:s' );
			$this->RequestToken   = DelectusModule::generate_token();
			$this->ClientToken    = DelectusModule::client_token();
			$this->SiteIdentifier = DelectusModule::site_identifier();
			$this->MemberID       = Member::currentUserID();
		}
		if ( $this->isChanged( 'Status' ) ) {
			$this->LastStatusDate = date( 'Y-m-d H:i:s' );
		}
	}

}