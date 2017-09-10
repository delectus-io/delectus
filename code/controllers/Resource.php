<?php

class DelectusResourceController extends Controller {
	private static $allowed_actions = [
		'remove' => '->canDelete',
		'edit' => '->canEdit'
	];
	private static $url_handlers = [
		'//$Relationship!/$ID!/delete' => 'remove',
		'//$Relationship!/$ID!/edit' => 'edit'
	];

	public function remove(SS_HTTPRequest $request) {
		$owner = DelectusModule::resource_owner();
		$relationship = $request->param('Relationship');

		if (!in_array($relationship, ['Files', 'Links'])) {
			return $this->httpError( 500 );
		}
		$owner->$relationship->remove($request->param('ID'));
		if (Director::is_ajax()) {
			return new SS_HTTPResponse(200);
		} else {
			return $this->redirectBack();
		}
	}

	public function edit(SS_HTTPRequest $request) {

	}

}