<?php

interface DelectusTransportInterface {
	public function makeRequest($request, &$resultMessage = '');
}