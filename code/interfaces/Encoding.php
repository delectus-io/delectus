<?php

interface DelectusEncodingInterface {
	/**
	 * Return encoded data, e.g. as a json_encoded string
	 * @param        $data
	 * @param string $contentType
	 * @param null   $options
	 *
	 * @return mixed
	 */
	public function encode( $data, $contentType = '', $options = null );

	/**
	 * Return unencoded data, e.g. an array from json_decode
	 * @param        $data
	 * @param string $contentType
	 * @param null   $options
	 *
	 * @return mixed
	 */
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