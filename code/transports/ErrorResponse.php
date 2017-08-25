<?php

class DelectusErrorResponse extends DelectusResponse {
	public function isOK() {
		return false;
	}
}