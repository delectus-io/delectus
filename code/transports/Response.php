<?php
abstract class DelectusResponse extends \Object {
	protected $code;
	protected $message;
	protected $data;

	public function __construct($data, $responseCode, $responseMessage) {
		$this->data = $data;
		$this->code = $responseCode;
		$this->message = $responseMessage;
		parent::__construct();
	}

	/**
	 * @return bool if OK, false otherwise. OK means e.g.  a 200 response code or other successfull call
	 */
	abstract public function isOK();

	public function getResponseCode() {
		return $this->code;
	}
	public function getResponseMessage() {
		return $this->message;
	}
	public function getData() {
		return $this->data;
	}
}

