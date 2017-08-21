<?php
interface DelectusEncryptionInterface {
	public function encrypt($data, $password, $options = null);

	public function decrypt($data, $password, $options = null);
}