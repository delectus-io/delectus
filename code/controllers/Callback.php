<?php

use Delectus\ModelController;

/**
 * Base class for controllers which handle callbacks from delectus services into
 * a client site.
 */
abstract class DelectusCallbackController extends ModelController {

	/**
	 * Check authentication and site tokens from request agains the current site tokens configured
	 * in SiteConfig or config yaml.
	 *
	 * @param \SS_HTTPRequest $request
	 *
	 * @return bool
	 * @throws \InvalidArgumentException
	 */
	protected function checkRequestIsValid( SS_HTTPRequest $request = null) {
		$request = $request ?: $this->getRequest();

		if ( DelectusModule::tokens_in_url() ) {
			$authToken = $request->getVar( DelectusModule::AuthTokenParameter );
			$siteToken = $request->getVar( DelectusModule::SiteIdentifierParameter );
		} else {
			$authToken = $request->getHeader( DelectusModule::AuthTokenHeader );
			$siteToken = $request->getHeader( DelectusModule::SiteIdentifierHeader );
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

		$authToken = DelectusModule::decrypt_data( $token, $clientSalt );

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

		$checkToken = DelectusModule::decrypt_data( $token, $clientSalt );

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