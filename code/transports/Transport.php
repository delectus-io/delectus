<?php

abstract class DelectusTransport extends \Object implements DelectusTransportInterface {

	/** @var \DelectusModule */
	protected $module;

	/** @var  \DelectusEncodingInterface */
	protected $encoder;

	/** @var  \DelectusEncodingInterface */
	protected $decoder;

	/** @var  \DelectusEncryptionInterface */
	protected $encrypter;

	/** @var  \DelectusEncryptionInterface */
	protected $decrypter;

	/**
	 * @param \DelectusModule $module
	 *
	 * @return $this
	 */
	public function setModule( DelectusModule $module ) {
		$this->module = $module;

		return $this;
	}

	/**
	 * @return \DelectusModule
	 */
	public function getModule() {
		return $this->module;
	}

	/**
	 * @param \DelectusEncodingInterface $encoder
	 *
	 * @return $this
	 */
	public function setEncoder( DelectusEncodingInterface $encoder ) {
		$this->encoder = $encoder;

		return $this;
	}

	/**
	 * @return \DelectusEncodingInterface
	 */
	public function getEncoder() {
		return $this->encoder ?: $this->decoder;
	}

	/**
	 * @param \DelectusEncodingInterface $decoder
	 *
	 * @return $this
	 */
	public function setDecoder( DelectusEncodingInterface $decoder ) {
		$this->decoder = $decoder;

		return $this;
	}

	/**
	 * @return \DelectusEncodingInterface
	 */
	public function getDecoder() {
		return $this->decoder ?: $this->encoder;
	}

	/**
	 * @param \DelectusEncryptionInterface $encrypter
	 *
	 * @return $this
	 */
	public function setEncrypter( DelectusEncryptionInterface $encrypter ) {
		$this->encrypter = $encrypter;

		return $this;
	}

	/**
	 * @return \DelectusEncryptionInterface
	 */
	public function getEncrypter() {
		return $this->encrypter ?: $this->decrypter;
	}

	/**
	 * @param \DelectusEncryptionInterface $decrypter
	 *
	 * @return $this
	 */
	public function setDecrypter( DelectusEncryptionInterface $decrypter ) {
		$this->decrypter = $decrypter;

		return $this;
	}

	/**
	 * @return \DelectusEncryptionInterface
	 */
	public function getDecrypter() {
		return $this->decrypter ?: $this->encrypter;
	}

	/**
	 * @param mixed $data
	 *
	 * @param null  $contentType
	 *
	 * @return string
	 */
	public function encode( $data, $contentType = null ) {
		return is_null( $contentType )
			? $this->getEncoder()->encode( $data )
			: $this->getEncoder()->encode( $data, $contentType );
	}

	/**
	 * @param string $data
	 *
	 * @param null   $contentType if null then default for decoder will be used
	 *
	 * @return mixed
	 */
	public function decode( $data, $contentType = null ) {
		return is_null( $contentType )
			? $this->getDecoder()->decode( $data )
			: $this->getDecoder()->decode( $data, $contentType );
	}

	/**
	 * return the mime type this encoder will encode as
	 *
	 * @return string e.g. 'application/json'
	 */

	public function contentType() {
		return $this->getEncoder()->contentType();
	}

	/**
	 * return the mime type of content the decoder will accept
	 *
	 * @return string e.g. 'application/json'
	 */
	public function acceptType() {
		return $this->getDecoder()->acceptType();
	}

	/**
	 * @param string $string   should already be encoded as a string
	 * @param string $password if null then the DelectusModule client_salt will be used
	 *
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function encrypt( $string, $password = null ) {
		$password = is_null( $password ) ? DelectusModule::client_salt() : $password;

		return $this->getEncrypter()->encrypt( $string, $password );
	}

	/**
	 * @param string $string   should be encoded as a string
	 * @param string $password if null then the DelectusModule client_salt will be used
	 *
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function decrypt( $string, $password = null ) {
		$password = is_null( $password ) ? DelectusModule::client_salt() : $password;

		return $this->getDecrypter()->decrypt( $string, $password );
	}

}