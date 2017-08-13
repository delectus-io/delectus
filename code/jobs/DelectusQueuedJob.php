<?php

abstract class DelectusQueuedJob extends AbstractQueuedJob implements QueuedJob {
	const Title = '';

	// set to the name of the service to use, e.g. 'DelectusIndexService'
	const ServiceName = '';

	public function __construct( DelectusApiRequestModel $request ) {
		$this->requestID = $request->ID;
		$this->title     = static::Title . ': ' . $request->Title;
	}

	public function getTitle() {
		return "$this->title (#$this->requestID)";
	}

	/**
	 *
	 */
	public function process() {
		/** @var \DelectusApiRequestModel $request */
		$request = DelectusApiRequestModel::get()->byID( $this->requestID );

		DB::query( "update " . DelectusApiRequestModel::class . " set Status = '" . $request::StatusSending . "', LastStatusDate = '" . date( 'Y-m-d H:i:s' ) . "' where ID = $request->ID and Status = '" . $request::StatusQueued . "'" );
		if ( DB::affected_rows() != 1 ) {
			// something else has grabbed the request in the meantime, skip it
			return;
		}

		$service = Injector::inst()->create(static::ServiceName);

		$timer = time();
		try {
			$request->Outcome = $request::OutcomeWaiting;

			if ( ! $response = $service->makeRequest( $request, true ) ) {
				throw new Exception( "Failed to make request" );
			}
		} catch ( Exception $e ) {
			$request->Status        = $request::StatusFailed;
			$request->Outcome       = $request::OutcomeFailure;
			$request->ResultMessage = $e->getMessage();

		}
		$request->RequestDuration = time() - $timer;
		$request->LastStatusDate  = date( 'Y-m-d H:i:s' );
		$request->write();

	}}