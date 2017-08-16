<?php

interface DelectusTransportInterface {
	/**
	 * @param DelectusApiRequestModel $request
	 * @param array                   $data optional data to add to request payload
	 *
	 * @return mixed
	 */
	public function makeRequest( DelectusApiRequestModel $request, $data = [] );

	public static function decrypt_data($data, $salt);

	public static function encrypt_data($data, $salt);
}