<?php

class DelectusModelAdmin extends ModelAdmin {
	private static $managed_models = [
		'DelectusApiRequestModel'
	];

	private static $url_segment = 'delectus';

	private static $menu_title = 'Delectus';
}