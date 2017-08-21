<?php

class DelectusJSONEncoding extends \Object implements DelectusEncodingInterface {
	const ContentType = 'application/json';

	private static $accept_type = self::ContentType;

	private static $content_type = self::ContentType;

	/**
	 * Encode data for transport, currently only json encoding is supported
	 *
	 * @param mixed  $data
	 * @param string $contentType not used at the moment
	 * @param null   $options not used here
	 *
	 * @return string
	 * @throws \DelectusException
	 */
	public function encode( $data, $contentType = self::ContentType, $options = null ) {
		if ( $contentType != $this->contentType()) {
			throw new DelectusException( "Bad content type '$contentType'" );
		}

		return json_encode( $data );
	}

	/**
	 * Decode data from transport, currently only json encoding is supported, may encrypt it
	 *
	 * @param mixed  $data
	 * @param bool   $asArray
	 * @param string $contentType not used at the moment
	 *
	 * @return mixed
	 * @throws \DelectusException
	 */
	public function decode( $data, $contentType = self::ContentType, $asArray = true)  {
		if ($contentType != $this->contentType()) {
			throw new DelectusException("Bad content type '$contentType'");
		}
		return json_decode( $data, $asArray);
	}

	public function acceptType() {
		return static::config()->get( 'accept_type' );
	}

	public function contentType() {
		return static::config()->get( 'content_type' );
	}
}