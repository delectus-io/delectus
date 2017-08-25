<?php

use DelectusException as Exception;

/**
 * DelectusApiRequestModel model records requests made to delectus services and is updated by
 * a callback from delectus when the request has been processed.
 *
 * @property string Source               - source of the request, e.g. 'PageExtension.onBeforeDelete'
 * @property string Version
 * @property string Endpoint            - 'index', 'search'
 * @property string Action              - 'add', 'remove', 'reindex'
 * @property string Status              - 'Queued', 'Sending', 'Sent', 'Failed', 'Completed'
 * @property string Outcome             - 'Waiting', 'Success', 'Failure'
 * @property string ModelClass
 * @property int    ModelID
 * @property string ModelToken          - model's token (e.g. for Page or File)
 * @property string ClientToken         - client token passed in request
 * @property string SiteIdentifier      - site id passed in request
 * @property string RequestToken        - unique token for this request
 * @property string RequestURL          - URL request was made to on remote service
 * @property int    RequestCount        - requests may double up, keep a count of how many times this was made
 * @property int    RetryCount          - queue handler should retry until RetryCount >= RequestCount
 * @property int    RunEpoch            - when this request should run
 * @property int    MemberID            - who was logged in when request was queued
 * @property int    ResultCode          - native code, e.g. 200 or 404
 * @property string ResultMessage       - empty until request has started process, should have description of ResultCode at end
 * @property string Headers             - in dev mode this will have json encoded version of data, encrypted in live mode
 * @property string Data                - in dev mode this will have json encoded version of data, encrypted in live mode
 * @property int    JobID               - id of Job if this was started via a job
 * @property string RequestDate         - original date request was made
 * @property int    RequestStart        - unix time request was physically started
 * @property int    RequestEnd          - unix time request was physically ended (fail or success or timeout)
 * @property string LastStatusDate      - last time the status changed
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
		'Source'         => 'Varchar(255)',
		'Status'         => 'Enum("Queued,Sending,Sent,Failed,Completed")',
		'Outcome'        => 'Enum("Waiting,Success,Failure")',
		'RunEpoch'       => 'Int',              // time() when the request should be satisfied
		'RequestCount'   => 'Int',
		'RetryCount'     => 'Int',
		'LastStatusDate' => 'SS_DateTime',
		'RequestDate'    => 'SS_DateTime',
		'RequestStart'   => 'Int',
		'RequestEnd'     => 'Int',
		'Headers'        => 'Text',
		'Data'           => 'Text',
	];

	private static $has_one = [
		'Member' => 'Member',
	];

	private static $summary_fields = [
		'Title'          => 'Description',
		'ModelTitle'     => 'Model Title',
		'ModelLink'      => 'Model Link',
		'ModelToken'     => 'Model Token',
		'Status'         => 'Status',
		'LastStatusDate' => 'Last Status Date',
		'Outcome'        => 'Outcome',
		'ResultCode'     => 'Result Code',
		'ResultMessage'  => 'Result Message',
	];

	/**
	 * Use Source for the title.
	 * @return string
	 */
	public function getTitle() {
		return $this->Source;
	}

	/**
	 * Return the model from ModelClass and ModelID or null if can't or it doesn't exist in database (anymore)
	 *
	 * @return \DataObject
	 * @throws \DelectusException
	 */
	public function getModel() {
		$model = null;
		if ( $this->ModelID && $this->ModelClass && ClassInfo::exists( $this->ModelClass ) ) {
			$modelClass = $this->ModelClass;
			$model      = $modelClass::get()->byID( $this->ModelID );
		}

		return $model;
	}

	public function ModelTitle() {
		if ( $model = $this->getModel() ) {
			return $model->Title;
		} else {
			return '';
		}
	}

	public function ModelLink() {
		if ( $model = $this->getModel() ) {
			return $model->Link();
		} else {
			return '';
		}
	}

	/**
	 * @param DataObject|\DelectusDataObjectExtension $model
	 */
	public function setModel( $model ) {
		$this->ModelClass = $model->ClassName;
		$this->ModelID    = $model->ID;
		$this->ModelToken = $model->{$model->modelTokenFieldName()};
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
		parent::onBeforeWrite();
	}

}