<?php
/**
 * Class DelectusForm
 * @property Init_Controller $controller
 */
class DelectusForm extends BootstrapForm {

	public function __construct($controller, $name, $fields, $actions, $validator) {
		/** @var FormField $field */
		foreach ($fields as $field) {
			$field->setAttribute('placeholder', $field->attrTitle());

			//added custom field templates to prevent cms fields overwriting
			switch ($field->Type()) {
				case 'text':
				case 'select2':
					// case 'extraimages':
					// $field->setFieldHolderTemplate("MosaicFormField_holder");
					break;
				case 'fileattachment':
					$fields->bootstrapIgnore($field->Name);
					break;

				default:
					//do nothing
					break;
			}
		}

		$this->addExtraClass("delectus-form");

		parent::__construct($controller, $name, $fields, $actions, $validator);
	}

	/**
	 * Shortcut method to get the current logged in user
	 * @return Member
	 */
	public function getCurrentUser() {
		return $this->controller->currentUser();
	}

	/**
	 * Shortcut method for redirection
	 * @return Member
	 */
	public function redirect($url, $code = 302) {
		return $this->controller->redirect($url, $code);
	}

	public function hasErrors() {
		return Session::get("FormInfo.{$this->FormName()}.errors");
	}

}