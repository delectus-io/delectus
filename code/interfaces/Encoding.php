<?php

interface DelectusEncodingInterface {
	public function encode( $data, $contentType = '', $options = null );

	public function decode( $data, $contentType = '', $options = null );

	/**
	 * contentType is the type that this encoder can encode (e.g. for sending)
	 * @return mixed
	 */
	public function contentType();

	/**
	 * acceptType is the content type this encoder can decode (e.g. for receiving)
	 * @return mixed
	 */
	public function acceptType();
}