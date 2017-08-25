<?php

abstract class DelectusQueuedJob extends AbstractQueuedJob implements QueuedJob {
	const Title = '';

	// set to the name of the service to use, e.g. 'DelectusIndexService'
	const ServiceName = '';

	private static $queue_name = 'Delectus';

	public function __construct( DelectusApiRequestModel $request = null ) {
		if ( $request ) {
			$this->requestID = $request->ID;
			$this->title     = static::Title . ': ' . $request->Title;
		}
	}

	public function getTitle() {
		return "$this->title (#$this->requestID)";
	}

	/**
	 * @return bool ok if processed succesfully, false if failed
	 * @throws \InvalidArgumentException
	 * @throws \ValidationException
	 */
	public function process() {
		/** @var \DelectusApiRequestModel $request */
		$request = DelectusApiRequestModel::get()->byID( $this->requestID );

		DB::query( "update " . DelectusApiRequestModel::class . " set Status = '" . $request::StatusSending . "', LastStatusDate = '" . date( 'Y-m-d H:i:s' ) . "' where ID = $request->ID and Status = '" . $request::StatusQueued . "'" );
		if ( DB::affected_rows() != 1 ) {
			// something else has grabbed the request in the meantime, skip it
			return null;
		}

		$service = Injector::inst()->create( static::ServiceName );

		$timer = time();
		try {
			$request->Status       = DelectusApiRequestModel::StatusSending;
			$request->Outcome      = $request::OutcomeWaiting;
			$request->RequestStart = $timer;
			$request->RunEpoch     = $request->RunEpoch ?: time();

			if ( ! $response = $service->makeRequest( $request ) ) {
				throw new Exception( "Failed to make request" );
			}

			$result = true;

		} catch ( Exception $e ) {
			$request->Status        = $request::StatusFailed;
			$request->Outcome       = $request::OutcomeFailure;
			$request->ResultMessage = $e->getMessage();

			$result = false;

		}
		$request->write();

		return $result;

	}

	/**
	 * Return the configured queue name for this job, defaults to 'Delectus'
	 *
	 * @return string
	 */
	public static function queue_name() {
		return \Config::inst()->get( static::class, 'queue_name' );
	}
}