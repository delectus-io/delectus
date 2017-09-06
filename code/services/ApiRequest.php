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

	// set in derived service class to response class name, e.g. 'DelectusIndexResponse'
	const SuccessResponseClassName = '';

	const ErrorResponseClassName = DelectusErrorResponse::class;

	// how far into the future the queued job should run ('now' for send immediately)
	private static $queue_delay = '+1 mins';

	// log file path relative to site root, '../' allowed. If not set then just default log settings will be used.
	private static $log_path = '';

	// log file name to create in path, if not set then the concrete class name will be used + '.log'
	private static $log_name = '';

	// logging equal to or 'worse' than this level
	private static $log_level = SS_Log::WARN;

	/**
	 * May be set by Injector, if not the Injector used to create a DelectusTransport
	 *
	 * @var \DelectusTransportInterface
	 */
	protected $transport;

	/**
	 * @var \DelectusModule
	 */
	protected $module;

	/**
	 * Set the module which 'owns' this request service for customisation of service calls and transport.
	 *
	 * @param \DelectusModule $module
	 *
	 * @return $this
	 */
	public function setModule( DelectusModule $module ) {
		$this->module = $module;

		return $this;
	}

	/**
	 * Return the module (generally set by DI)
	 *
	 * @return \DelectusModule
	 */
	public function getModule() {
		return $this->module;
	}

	/**
	 * Called by injector to set transport property.
	 *
	 * @param \DelectusTransportInterface $transport
	 *
	 * @return $this
	 */
	public function setTransport( DelectusTransportInterface $transport ) {
		$this->transport = $transport;

		return $this;
	}

	/**
	 * Return transport set by DI or created by Injector explicitly (required module to have been set)
	 *
	 * @return \DelectusTransportInterface
	 */
	public function getTransport() {
		if ( ! $this->transport ) {
			$this->transport = \Injector::inst()->create( self::TransportName, $this->getModule() );
		}

		return $this->transport;
	}

	/**
	 * Either queues or sends the request immediately depending on the RunEpoch being later or equal to or before now.
	 *
	 * @param \DelectusApiRequestModel $request
	 *
	 * @return \DelectusResponse|null
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 * @throws \ValidationException
	 */
	public function makeRequest( DelectusApiRequestModel $request ) {
		$request->Endpoint       = static::Endpoint;
		$request->ClientToken    = DelectusModule::client_token();
		$request->Version        = DelectusModule::version();
		$request->SiteIdentifier = DelectusModule::site_identifier();
		$request->ResultCode     = 0;
		$request->ResultMessage  = null;

		$logWriter = null;

		if ( $logPath = $this->config()->get( 'log_path' ) ) {
			if ($logPath = realpath(Controller::join_links( BASE_PATH, $logPath))) {
				$logName = $this->config()->get( 'log_name' ) ?: ( static::class . '.log' );

				$logPathName = Controller::join_links($logPath, $logName);

				// set to config.log_level if set, otherwise DEBUG if in dev mode, or WARN otherwise
				$logLevel = $this->config()->get('log_level') ?: ( Director::isDev() ? SS_Log::DEBUG : SS_Log::WARN );

				SS_Log::add_writer( $logWriter = new SS_LogFileWriter( $logPathName ), $logLevel, '>=' );
			}
		}

		SS_Log::log( __METHOD__, SS_Log::DEBUG );

		$immediate = $request->RunEpoch
			? $request->RunEpoch <= time()
			: false;

		if ( $immediate || $this->config()->get( 'send_immediate' ) ) {
			$request->RunEpoch = time();

			$result = $this->sendRequest( $request );

			if ( $result->isOK() ) {
				$result = $this->handleSuccess( $result );
			} else {
				$result = $this->handleError( $result );
			}

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
			$request->ResultMessage = $resultMessage;
		}
		$request->write();

		if ( $logWriter ) {
			SS_Log::remove_writer( $logWriter );
		}

		return $result ?: null;
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
		SS_Log::log( __METHOD__, SS_Log::DEBUG );

		$queueService = singleton( 'QueuedJobService' );

		$job = new DelectusIndexJob( $request );

		if ( $request->RunEpoch ) {
			$runWhen = date( 'Y-m-d H:i:s', $request->RunEpoch );
		} else {
			$runWhen = date( 'Y-m-d H:i:s', strtotime( static::config()->get( 'queued_delay' ) ) );
		}

		$jobID = $queueService->queueJob(
			$job,
			$runWhen,
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
	 * Make the request immediately to delectus service, updates $request with outcome
	 *
	 * @param \DelectusApiRequestModel $request
	 *
	 * @return \DelectusResponse
	 * @throws \Exception
	 * @throws \ValidationException
	 */
	protected function sendRequest( DelectusApiRequestModel $request ) {
		SS_Log::log( __METHOD__, SS_Log::DEBUG );

		$result = null;

		$resultCode    = 0;
		$resultMessage = '';

		$errorResponseClassName   = static::ErrorResponseClassName;
		$successResponseClassName = static::SuccessResponseClassName;

		try {
			if ( $model = $request->getModel() ) {
				$request->Status = $request::StatusSending;
				$request->write();

				/** @var \DelectusTransportInterface $transport */
				$transport = $this->getTransport();

				$result = $transport->makeRequest( $request, $resultCode, $resultMessage );

				$request->Status = $request::StatusCompleted;

				if ( $transport->isOK( $result ) ) {
					$response         = new $successResponseClassName( $result, $resultCode, $resultMessage );
					$request->Outcome = $request::OutcomeSuccess;
				} else {
					$response         = new $errorResponseClassName( $result, $resultCode, $resultMessage );
					$request->Outcome = $request::OutcomeFailure;
				}

			} else {
				throw new \Exception( "No such model", $request::StatusFailed );
			}
		} catch ( Exception $e ) {
			$request->Status  = $request::StatusFailed;
			$request->Outcome = $request::OutcomeFailure;

			$response         = new $errorResponseClassName( null, $e->getCode(), $e->getMessage() );
		}

		$request->ResultMessage = $resultMessage;
		$request->ResultCode    = $resultCode;
		$request->write();

		return $response;
	}

	/**
	 * @param \DelectusResponse $response
	 *
	 * @return bool true if handled, false otherwise
	 */
	protected function handleSuccess( DelectusResponse $response ) {
		SS_Log::log( __METHOD__, SS_Log::INFO );

		return $response->isOK();
	}

	/**
	 * @param \DelectusResponse $response
	 *
	 * @return bool true if handled, false otherwise
	 */
	protected function handleError( DelectusResponse $response) {
		SS_Log::log( __METHOD__ . ': ' . $response->getResponseMessage(), SS_Log::WARN );

		return true;
	}

	/**
	 * Check to see if the last request made for the model is the same as this one, if so return the last request model,
	 * otherwise initialise a request object, set the model, write and return it.
	 *
	 * @param DataObject|\DelectusModelExtension $model
	 * @param string                             $description of what request does
	 * @param string                             $action
	 *
	 * @return \DelectusApiRequestModel
	 * @throws \InvalidArgumentException
	 * @throws \ValidationException
	 */
	protected static function enqueue_request( $model, $description, $action ) {
		/** @var \DelectusApiRequestModel $request */
		// find the last request for the model
		$request = DelectusApiRequestModel::get()->filter( [
			'ModelClass' => $model->ClassName,
			'ModelID'    => $model->ID,
		] )->sort( 'LastStatusDate', 'DESC' )->limit( 1 )->first();

		if ( $request ) {
			// there was a last request, if it is the same action then increment the request count
			// and the queue handling should pick it up for a retry
			if ( $request->Action == $action ) {
				$request->RequestCount ++;
				$enqueue = false;
			} else {
				$enqueue = true;
			}
		} else {
			$enqueue = true;
		}
		if ( $enqueue ) {

			$request = new DelectusApiRequestModel( [
				'Title'        => $description,
				'Action'       => $action,
				'Link'         => $model->Link(),
				'RequestCount' => 1,
			] );
			$request->setModel( $model );

		}
		$request->write();

		return $request;
	}
}