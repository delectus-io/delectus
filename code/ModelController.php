<?php
use DelectusException as Exception;

abstract class DelectusModelController extends \ContentController {

	// controllers handle one main model, ie restful
	const ModelClass = '';

	// name of the Token parameter when docoding url params e.g. in '/$Token!/$Action!'
	const ModelToken = 'Token';

	// first url segment for this controller, e.g. 'client' for ClientController, should be kept in sync with Director.rules in routes.yml
	const URLSegment = '';

	const ActionCreate = 'add';
	const ActionRead   = 'view';
	const ActionUpdate = 'edit';
	const ActionDelete = 'remove';
	const ActionSearch = 'search';

	const MethodCreate = 'add';
	const MethodView   = 'view';
	const MethodEdit   = 'edit';
	const MethodDelete = 'remove';
	const MethodSearch = 'search';

	private static $url_handlers = [
		self::ActionCreate                                 => self::MethodCreate,
		'$' . self::ModelToken . '!/' . self::ActionUpdate => self::MethodEdit,
		'$' . self::ModelToken . '!/' . self::ActionRead   => self::MethodView,
		'$' . self::ModelToken . '!/' . self::ActionDelete => self::MethodDelete,
		'$' . self::ModelToken . '!'                       => self::MethodView,
	];
	private static $allowed_actions = [
		self::MethodCreate => '->canCreate',
		self::MethodView   => '->canView',
		self::MethodEdit   => '->canEdit',
		self::MethodDelete => '->canDelete',
	];
	// fields declared here will be added to the model's fields for action
	private static $fields_for_actions = [
	];

	/**
	 * Controllers return themselves when invoked.
	 *
	 * @return $this
	 */
	public function __invoke() {
		return $this;
	}

	/**
	 * Return the client from the request (either by auth token or login)
	 *
	 * @return \Delectus\Core\Models\Client
	 */
	abstract public function currentClient();

	/**
	 * Return the model from the request
	 *
	 * @return \Delectus\Core\Model
	 */
	abstract public function currentModel();

	/**
	 * Return the client for the current model (resolve relationships from currentModel to its Client)
	 *
	 * @return \Delectus\Core\Models\Client
	 */
	abstract public function currentModelClient();

	/**
	 * Render template, json or xml to output
	 *
	 * @param array $data
	 *
	 * @return mixed
	 */
	abstract protected function renderResponse( array $data = [] );


	public function index( SS_HTTPRequest $request ) {
		return $this->renderWith(
			$this->templates( $request ),
			[
				'Client' => $this->currentClient(),
				'Model'  => $this->currentModel(),
			]
		);
	}

	/**
	 * @param SS_HttpRequest $request
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function templates( $request ) {
		$modelClass = $this->getModelClass();
		$action     = $this->getAction();

		$templateNames = [
			"{$modelClass}_$action",
			"$modelClass",
			"Page",
		];

		return $templateNames;
	}

	public function getModelClass() {
		if ( ! static::ModelClass ) {
			throw new Exception( "Controller has no ModelClass set" );
		}

		return static::ModelClass;
	}

}