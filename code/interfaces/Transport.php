<?php

interface DelectusTransportInterface {
	/**
	 * @param DelectusApiRequestModel $request meta data and data to send and will contain result meta data, code, message etc
	 *
	 * @param                         $resultCode
	 * @param                         $resultMessage
	 *
	 * @return mixed data returned, null or false if failed
	 */
	public function makeRequest( DelectusApiRequestModel $request, &$resultCode, &$resultMessage);

	/**
	 * @param $result
	 *
	 * @return bool true if result is 'OK', false otherwise
	 */
	public function isOK($result);

	public function decrypt($data, $password);

	public function encrypt($data, $password);

	public function encode($data, $contentType);

	public function decode($data, $contentType);
}