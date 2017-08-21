<?php

class DelectusOpenSSLEncryption extends \Object implements DelectusEncryptionInterface {

	// default openssl encryption used if not configured elsewhere.
	private static $encryption_algorythm = 'aes-256-ctr';

	/**
	 * Encrypt data using password, e.g. ClientSalt and then base64_encode it. Data is
	 * first padded left with a throw-away token.
	 *
	 * @param string $data     must already converted to a string, e.g. via json_encode
	 * @param string $password e.g client_salt if empty then no encryption will be done
	 *
	 * @param null   $options not used here
	 *
	 * @return string which is base64_encoded
	 * @throws \Exception
	 */
	public function encrypt( $data, $password, $options = null ) {
		$data = DelectusModule::generate_token() . $data;

		if ( $password && $algorythm = static::encryption_algorythm() ) {
			$data = openssl_encrypt( $data, $algorythm, $password );
		}

		return base64_encode( $data );
	}

	/**
	 * Decrypt data using password, e.g. client_salt after base64_decoding it first. Strips off
	 * first token length padding before returning.
	 *
	 * @param string $data     base64_encoded data
	 * @param string $password e.g. client_salt if empty then no decryption will be done
	 * @param null   $options not used here
	 *
	 * @return string
	 */
	public function decrypt( $data, $password, $options = null ) {
		$data = base64_decode($data);

		if ( $password && $algorythm = static::encryption_algorythm() ) {
			$data = openssl_decrypt(
				$data,
				$algorythm,
				$password
			);
		}

		return substr( $data, DelectusModule::TokenLength );
	}

	/**
	 * Return nothing for Dev mode, DelectusModule encryption algorythm if set
	 * otherwise this classes config.encryption_algorythm
	 *
	 * @return string e.g. 'aes-256-ctr' that openssl understands.
	 */
	protected static function encryption_algorythm() {
		return Director::isDev()
			? ''
			: (DelectusModule::encryption_algorythm()
				?: static::config()->get('encryption_algorythm')
			);
	}

}