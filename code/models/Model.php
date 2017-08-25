<?php

use DelectusException as Exception;

/**
 * DelectusModel common functionality and fields for a Delectus model
 */
/* abstract */ class DelectusModel extends \DataObject {
	// use this field name to find the token in requests e.g. 'ClientToken';
	const RequestTokenFieldName = '';
	// name of the token field for this model, e.g. 'DelectusClientToken';
	const ModelTokenFieldName = '';

	public function modelTokenFieldName() {
		return static::ModelTokenFieldName;
	}

	/**
	 * @param string $token unencrypted token to match on
	 *
	 * @return $this|null
	 */
	public static function get_by_token($token) {
		if ($token) {
			$modelClass = static::class;

			return $modelClass::get()->find( static::ModelTokenFieldName, $token );

		}
		return null;
	}

	/**
	 * Locator method use token for this model to find and return the model.
	 *
	 * @param \SS_HTTPRequest $request
	 *
	 * @return DataObject|\Delectus\Core\Model|null
	 * @throws \Exception
	 */
	public static function find_requested( \SS_HTTPRequest $request ) {
		if ( ! static::RequestTokenFieldName ) {
			throw new Exception( "RequestDataTokenName not set on concrete class" );
		}

		if ( ! $data = DelectusModule::transport()->request_data( $request ) ) {
			throw new Exception( "No data" );
		}
		if ( ! isset( $data[ static::RequestTokenFieldName ] ) ) {
			throw new \Exception( ( "No token '" . static::RequestTokenFieldName . "'" ) );
		}
		$token      = $data[ static::RequestTokenFieldName ];
		$modelClass = static::class;
		$model      = $modelClass::get()->find( static::RequestTokenFieldName, $token );

		if ( ! $model ) {
			throw new Exception( "Couldn't get model from request" );
		}

		return $model;
	}

	/**
	 * Factory method create a new model from request data. Doesn't write the model or validate has_one relationships.
	 *
	 * @param \SS_HTTPRequest $request
	 *
	 * @param array           $extraData to merge after request data
	 *
	 * @return \DataObject
	 * @throws \DelectusException
	 * @throws \Exception
	 * @internal param array $extraData
	 */
	public static function from_request( SS_HTTPRequest $request, $extraData = [] ) {
		if ( ! static::RequestTokenFieldName ) {
			throw new Exception( "RequestDataTokenName not set on concrete class" );
		}

		if ( ! $data = DelectusModule::transport()->request_data( $request ) ) {
			throw new Exception( "No data" );
		}
		if ( ! isset( $data[ static::RequestTokenFieldName ] ) ) {
			throw new \Exception( ( "No token '" . static::RequestTokenFieldName . "'" ) );
		}
		$modelClass = static::class;
		/** @var \DataObject $model */
		$model = $modelClass::create();

		$model->update( array_merge(
			[
				array_intersect_key(
					$request->postVars(),
					array_merge(
						$model->db() ?: [],
						$model->hasOne() ?: []
					)
				)
			],
			$extraData
		));

		return $model;

	}
}