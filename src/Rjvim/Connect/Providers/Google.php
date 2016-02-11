<?php namespace Rjvim\Connect\Providers;

use Config;
use Google_Client;
use Request;
use Redirect;
use Google_Service_Plus;
use Rjvim\Connect\Models\OAuthAccount;

class Google implements ProviderInterface{


	protected $client;
	protected $scopes;
	protected $sentry;

	/**
	 * Constructor for Connect Library
	 */
	public function __construct($client, $scope, $state = 'default')
	{
		$this->scopes = $scope;
		$this->client = $this->prepareClient($client,$scope,$state);

		$this->sentry = \App::make('sentry');
	}

	/**
	 * Prepare a Google Client with client id and scope
	 *
	 * @return void
	 * @author 
	 **/
	public function prepareClient($client, $scope, $state = 'default')
	{

		$client = Config::get('rjvim.connect.google.clients.'.$client);

		if(is_array($scope))
		{
			$scopes = array();

			foreach($scope as $s)
			{
				$scopes = array_merge(Config::get('rjvim.connect.google.scopes.'.$s),$scopes);
			}
		}
		else
		{
			$scopes = Config::get('rjvim.connect.google.scopes.'.$scope);
		}

		$gClient = new Google_Client();

		$gClient->setClientId($client['client_id']);
		$gClient->setClientSecret($client['client_secret']);
		$gClient->setRedirectUri($client['redirect_uri']);

		
		if(Config::get('rjvim.connect.offline'))
		{
			$gClient->setAccessType('offline');
		}

		
		if(Config::get('rjvim.connect.force'))
		{
			$gClient->setApprovalPrompt('force');
		}

		$gClient->setScopes($scopes);

		$gClient->setState($state);

		return $gClient;
	}

    /**
	 * Get User Data from Google
	 *
	 * @return void
	 * @author 
	 **/
	public function getGoogleUserData()
	{
		$result = array();

		$plus = new Google_Service_Plus($this->client);

		$person = $plus->people->get('me');

		if($person->getEmails()[0]->getType() == 'account')
		{
			$email = $person->getEmails()[0]->getValue();
		}
		else
		{
			$email = 'Not Found';
		}

		$result['uid'] = $person->id;

		if($this->sentry->check())
		{
			$result['email'] = $this->sentry->getUser()->email;
		}
		else
		{
			$result['email'] = $email;
		}
			
		$result['first_name'] = $person->getName()->getGivenName();
		$result['last_name'] = $person->getName()->getFamilyName();
		$result['username'] = $result['first_name'].' '.$result['last_name'];
		$result['name'] = $result['first_name'].' '.$result['last_name'];
		$result['url'] = $person->getUrl();
		$result['image'] = $person->getImage()->getUrl();

		$result['description'] = $person['aboutMe'];
		$result['gender'] = $person['gender'];
		$result['birthday'] = $person['birthday'];

		return $result;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function authenticate()
	{
		return Redirect::to($this->getAuthUrl());
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function takeCare()
	{
		$req = Request::instance();

		//d($req->get('state'));

		//If the request has code, authenticate and get tokens
		$this->client->authenticate($req->get('code'));

		//Once you get the token, get email address and data of the user
		$result = $this->getGoogleUserData();

		//d($result);

		//d($this->getChannelsList());

		return $result;

	}

	/**
	 * Update Google OAuth information for user
	 *
	 * @return void
	 * @author 
	 **/
	public function updateOAuthAccount($user,$gUserData)
	{
		$response = $this->client->getAccessToken();

		$scope = $this->scopes;

		$actual_response = $response;

		$response = json_decode($response);

		$oauth = OAuthAccount::firstOrCreate(
						array(
							'user_id' => $user->id, 
							'provider' => 'google'
						));

		$oauth->image_url = $gUserData['image'];
		$oauth->url = $gUserData['url'];
		$oauth->uid = $gUserData['uid'];
		$oauth->username = $gUserData['username'];

		$oauth->description = $gUserData['description'];
		$oauth->gender = $gUserData['gender'];
		$oauth->birthday = $gUserData['birthday'];

		$oauth->access_token = $response->access_token;

		if(isset($response->refresh_token))
		{
			$oauth->refresh_token = $response->refresh_token;
		}

		if(isset($response->created))
		{
			$oauth->created = $response->created;
		}

		$oauth->expires_in = $response->expires_in;

		$oauth->signature = serialize($actual_response);

		if(!is_array($scope))
		{
			$scope = (array) $scope;
		}

		$scopes = array();

		foreach($scope as $s)
		{

			$scopes['google.'.$s] = 1;

		}


		$oauth->scopes = $scopes;

		$oauth->save();

		return true;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function getAuthUrl()
	{
		return $this->client->createAuthUrl();
	}


}
