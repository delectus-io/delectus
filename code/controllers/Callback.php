<?php

/**
 * Base class for controllers which handle callbacks from delectus services into
 * a client site.
 */
abstract class DelectusCallbackController extends \DelectusApiRequestController {

	/**
	 * Check authentication and site tokens from request against the current site tokens configured
	 * in SiteConfig or config yaml.
	 *
	 * @param \SS_HTTPRequest $request
	 *
	 * @return bool
	 * @throws \InvalidArgumentException
	 */
	protected function checkRequestIsValid( SS_HTTPRequest $request = null) {
		$request = $request ?: $this->getRequest();

		$transport = DelectusModule::transport();

		if ( DelectusModule::tokens_in_url() ) {
			$authToken = $request->getVar( $transport::AuthTokenParameter );
			$siteToken = $request->getVar( $transport::SiteIdentifierParameter );
		} else {
			$authToken = $request->getHeader( $transport::AuthTokenHeader );
			$siteToken = $request->getHeader( $transport::SiteIdentifierHeader );
		}
		return $this->checkAuthToken( $authToken ) && $this->checkSiteIdentifier( $siteToken );
	}

	/**
	 * Check the token which may be encrypted, e.g. from X-Client-Secret header
	 * against this sites configured ClientSecret (from SiteConfig or yaml config)
	 *
	 * @param string $token
	 *
	 * @return bool true if match, false if not
	 * @throws \InvalidArgumentException
	 */
	protected function checkAuthToken( $token ) {
		$clientToken = DelectusModule::client_token();
		$clientSalt  = DelectusModule::client_secret();

		$authToken = DelectusModule::transport()->decrypt_data( $token, $clientSalt );

		return $authToken && ( $clientToken == $authToken );
	}

	/**
	 * Check the token which may be encrypted, e.g. from X-Site-Identifier header
	 * against this sites configured SiteIdentifier (from SiteConfig or yaml config)
	 *
	 * @param string $token
	 *
	 * @return bool true if match, false if not
	 * @throws \InvalidArgumentException
	 */
	public function checkSiteIdentifier( $token ) {
		$siteToken  = DelectusModule::site_identifier();
		$clientSalt = DelectusModule::client_salt();

		$checkToken = DelectusModule::transport()->decrypt_data( $token, $clientSalt );

		return $checkToken && ( $siteToken == $checkToken );
	}

	/**
	 * @param array $itemInfo
	 *
	 * @return DataObject|mixed
	 */
	public function findModelForItemInfo( $itemInfo ) {
		if ( $itemInfo['Type'] == 'Model' ) {
			list( $className, $id ) = explode( '|', $itemInfo['Data'] );
			if ( ClassInfo::exists( $className ) ) {
				return $className::get()->byID( $id );
			}
		}

		return $itemInfo['Data'];
	}

}