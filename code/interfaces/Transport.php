<?php

interface DelectusTransportInterface {
	/**
	 * @param DelectusApiRequestModel $request
	 * @param array                   $data optional data to add to request payload
	 *
	 * @return mixed
	 */
	public function makeRequest( DelectusApiRequestModel $request, $data = [] );

	public function decrypt($data, $password);

	public function encrypt($data, $password);

	public function encode($data, $contentType);

	public function decode($data, $contentType);
}