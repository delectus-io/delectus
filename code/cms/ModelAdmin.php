<?php

class DelectusModelAdmin extends ModelAdmin {
	private static $managed_models = [
		'DelectusApiRequest'
	];

	private static $url_segment = 'delectus';

	private static $menu_title = 'Delectus';
}