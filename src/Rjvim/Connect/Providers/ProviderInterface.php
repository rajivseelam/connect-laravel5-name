<?php namespace Rjvim\Connect\Providers;

interface ProviderInterface {
	
	public function authenticate();

	public function takeCare();

	public function updateOAuthAccount($user,$userData);

}