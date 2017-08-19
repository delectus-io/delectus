<?php
use DelectusException as Exception;

/**
 * Base class for services which make requests to the delectus services.
 */
class DelectusApiRequestService extends \Object {
	// should be defined in derived class, identified which config.endpoints to use
	const Endpoint = '';

	// passed to Injector to create the transport
	const TransportName = 'DelectusTransport';

	// how far into the future the queued job should run ('now' for send immediately)
	private static $queue_delay = '+1 mins';

	/**
	 * May be set by Injector, if not the Injector used to create a DelectusTransport
	 * @var \DelectusTransportInterface
	 */
	protected $transport;

	/**
	 * Called by injector to set transport property.
	 * @param \DelectusTransportInterface $transport
	 */
	public function setTransport(DelectusTransportInterface $transport) {
		$this->transport = $transport;
	}

	/**
	 * Return transport set by DI or created by Injector explicitly
	 * @return mixed
	 */
	public function getTransport() {
		if (!$this->transport) {
			$this->transport = \Injector::inst()->get( self::TransportName );
		}
		return $this->transport;
	}

	/**
	 * @param \DelectusApiRequestModel $request
	 * @param bool                     $immediate send now, overrides config.send_immediate, otherwise will queue
	 *
	 * @return bool|int|mixed
	 * @throws \DelectusException
	 * @throws \InvalidArgumentException
	 * @throws \ValidationException
	 */
	public function makeRequest( DelectusApiRequestModel $request, $immediate = false ) {
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
	 * @param \DelectusApiRequestModel $request
	 *
	 * @return int
	 */
	protected function queueRequest( DelectusApiRequestModel $request ) {
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
	 * @param \DelectusApiRequestModel $request
	 * @param string                   $resultMessage
	 *
	 * @return bool|mixed
	 * @throws \DelectusException
	 * @throws \ValidationException
	 */
	protected function sendRequest( DelectusApiRequestModel $request, &$resultMessage = '' ) {
		$result = null;
		if ( $model = $request->getModel() ) {
			$request->Status = $request::StatusSending;
			$request->write();

			try {
				$transport = $this->getTransport();

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
	 * @return \DelectusApiRequestModel
	 * @throws \ValidationException
	 */
	protected static function log_request( $model, $description, $action ) {
		$request = new DelectusApiRequestModel( [
			'Title'  => $description,
			'Action' => $action,
			'Link'   => $model->Link(),
		] );
		$request->setModel( $model );
		$request->write();

		return $request;

	}

}