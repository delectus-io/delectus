<?php

/**
 * Base class for services which make requests to the delectus services.
 */
class DelectusApiRequestService extends \Object {
	// should be defined in derived class, identified which config.endpoints to use
	const Endpoint = '';

	// passed to Injector to create the transport
	const TransportName = 'DelectusTransport';

	// wether to send request to delectus services immediately or via a queuedjob
	private static $send_immediate = false;

	// how far into the future the queued job should run if send_immediate is false
	private static $queue_delay = '+1 mins';

	/**
	 * Set to the client token you have been allocated, used to communicate with the delectus service
	 *
	 * @var string
	 */
	private static $client_token = '';

	/**
	 * Set to the client secret you have been assigned, used to secure information sent to the delectus service.
	 *
	 * @var string
	 */
	private static $client_salt = '';

	/**
	 * Set to the site id for the current site, used to communicate with the delectus service
	 *
	 * @var string
	 */
	private static $site_identifier = '';

	/**
	 * Endpoints for this server in form of https://api.delectus.io/
	 *
	 * @var array
	 */
	private static $endpoints = [
		#   'index' => https://api.delectus.io/
	];
	/**
	 * API version this module targets.
	 *
	 * @var string
	 */
	private static $version = 'v1';

	/**
	 * @param \DelectusApiRequest $request
	 * @param bool                $immediate send now, overrides config.send_immediate, otherwise will queue
	 *
	 * @return bool|int|mixed
	 * @throws \InvalidArgumentException
	 * @throws \ValidationException
	 */
	public function makeRequest( DelectusApiRequest $request, $immediate = false ) {
		$request->Endpoint       = static::Endpoint;
		$request->ClientToken    = DelectusModule::client_token();
		$request->Version        = DelectusModule::version();
		$request->SiteIdentifier = DelectusModule::site_identifier();
		$request->ResultCode     = 0;
		$request->ResultMessage  = null;

		if ( $immediate || $this->config()->get( 'send_immediate' ) ) {
			$result = $this->sendRequest( $request, $resultMessage );
		} else {
			if ( $result = $this->queueRequest( $request ) ) {
				$resultMessage = _t(
					'DelectusIndexService.QueueOKMessage',
					'Job queued with id {jid} for request with id {rid}',
					[
						'jid' => $result,
						'rid' => $request->ID,
					]
				);
			} else {
				$resultMessage = _t(
					'DelectusIndexService.QueueFailedMessage',
					'Failed to queue request with id {id} as a job',
					[
						'id' => $request->ID,
					]
				);
			}
		}
		$request->ResultMessage = $resultMessage;
		$request->write();

		return $result;
	}

	/**
	 * Submit the request as a QueuedJob for picking up later. updates $request
	 * but doesn't write it.
	 *
	 * @param \DelectusApiRequest $request
	 *
	 * @return int
	 */
	protected function queueRequest( DelectusApiRequest $request ) {
		$queueService = new QueuedJobService();

		$jobID = $queueService->queueJob(
			new DelectusIndexJob( $request ),
			date( 'Y-m-d H:i:s', strtotime( static::config()->get( 'queued_delay' ) ) ),
			null,
			'Delectus'
		);
		if ( $jobID ) {
			$request->JobID  = $jobID;
			$request->Status = $request::StatusQueued;
		} else {
			$request->Status = $request::StatusFailed;
		}

		return $jobID;

	}

	/**
	 * Make the requst direct to delectus service, updates $request but doesn't write it.
	 *
	 * @param \DelectusApiRequest $request
	 *
	 * @param string              $resultMessage
	 *
	 * @return bool|mixed
	 * @throws \ValidationException
	 */
	protected function sendRequest( DelectusApiRequest $request, &$resultMessage = '' ) {
		$result = null;
		if ( $model = $request->getModel() ) {
			$request->Status = $request::StatusSending;
			$request->write();

			try {
				$transport = static::transport();

				$result = $transport->makeRequest( $request );

				if ( $result ) {
					$request->Status = $request::StatusSent;
				} else {
					$request->Status = $request::StatusFailed;
				}

			} catch ( Exception $e ) {
				$request->Status        = $request::StatusFailed;
				$request->ResultMessage = $e->getMessage();
				$request->ResultCode    = $e->getCode();
			}
			$request->write();
		} else {
			$request->Status        = $request::StatusFailed;
			$request->ResultMessage = "No such model";
		}
		$resultMessage = $request->ResultMessage;

		return $result;
	}

	/**
	 * Initialise a request object, set the model, write and return it.
	 *
	 * @param DataObject $model
	 * @param string     $description of what request does
	 * @param string     $action
	 *
	 * @return \DelectusApiRequest
	 * @throws \ValidationException
	 */
	protected static function log_request( $model, $description, $action ) {
		$request = new DelectusApiRequest( [
			'Title'  => $description,
			'Action' => $action,
			'Link'   => $model->Link()
		] );
		$request->setModel( $model );
		$request->write();

		return $request;

	}

	/**
	 * return a transport using self.TransportName as the service name for Injector
	 *
	 * @return DelectusTransportInterface
	 */
	public static function transport() {
		return Injector::inst()->create( static::TransportName );
	}

}