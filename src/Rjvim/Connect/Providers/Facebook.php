<?php namespace Rjvim\Connect\Providers;

use Config;
use Redirect;
use Request;
use Rjvim\Connect\Models\OAuthAccount;

use Rjvim\Connect\Providers\LaravelFacebookRedirectLoginHelper;
use Facebook\FacebookSession;
use Facebook\FacebookRequestException;
use Facebook\FacebookRequest;
use Facebook\GraphUser;

class Facebook implements ProviderInterface{


	protected $client;
	protected $scopes;
	protected $sentry;

	/**
	 * Constructor for Connect Library
	 */
	public function __construct($client, $scope)
	{
		$this->scopes = $scope;

		$this->client = $client;

		$this->sentry = \App::make('sentry');

		$config = Config::get('connect::facebook.clients.'.$client);

		FacebookSession::setDefaultApplication($config['client_id'], $config['client_secret']);
	}

	public function getScopes()
	{

		if(is_array($this->scopes))
		{
			$scopes = array();

			foreach($this->scopes as $s)
			{
				$scopes = array_merge(Config::get('connect::facebook.scopes.'.$s),$scopes);
			}
		}
		else
		{
			$scopes = Config::get('connect::facebook.scopes.'.$this->scopes);
		}

		return $scopes;

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
	 * Find user using sentry methods
	 *
	 * @return void
	 * @author 
	 **/
	public function findUser($email)
	{

		try
		{

			$user = $this->sentry->findUserByLogin($email);

			$result['found'] = true;
			$result['user'] = $user;

		}
		catch (\Cartalyst\Sentry\Users\UserNotFoundException $e)
		{
		    $result['found'] = false;
		}

		return $result;

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

		$config = Config::get('connect::facebook.clients.'.$this->client);

		$helper = new LaravelFacebookRedirectLoginHelper($config['redirect_uri']);

		try {

		  $session = $helper->getSessionFromRedirect();

		} catch(FacebookRequestException $ex) {

			dd($ex);

		} catch(\Exception $ex) {

		  	dd($ex);
		}

		if($session)
		{

		  try {

		    $user_profile = (new FacebookRequest(
		      $session, 'GET', '/me'
		    ))->execute()->getGraphObject(GraphUser::className());

		    $user_image = (new FacebookRequest(
		      $session, 'GET', '/me/picture',
						  array (
						    'redirect' => false,
						    'height' => '200',
						    'type' => 'normal',
						    'width' => '200',
						  )
		    ))->execute()->getGraphObject()->asArray();

		  } catch(FacebookRequestException $e) {

		    dd('There was some error!');

		  } 
		  catch(FacebookSDKException $e) {

		    dd('There was some error!');

		  } 

			$result['uid'] = $user_profile->getId();

			if($this->sentry->check())
			{
				$result['email'] = $this->sentry->getUser()->email;
			}
			else
			{
				$result['email'] = $user_profile->getEmail();
			}
			
			$result['url'] = $user_profile->getLink();
			$result['location'] = $user_profile->getLocation();

			$fresult = $this->findUser($result['email']);

			if($fresult['found'])
			{
				$fuser = $fresult['user'];

				$result['first_name'] = $fuser->first_name;
				$result['last_name'] = $fuser->last_name;
			}
			else
			{

				$result['first_name'] = $user_profile->getFirstName();
				$result['last_name'] = $user_profile->getLastName();

			}

			$result['username'] = $user_profile->getFirstName().' '.$user_profile->getLastName();
			$result['name'] = $result['username'];

			$result['access_token'] = $session->getLongLivedSession()->getToken();

			if(!$user_image['is_silhouette'])
			{
				$result['image_url'] = $user_image['url'];
			}

			return $result;

		}

	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function updateOAuthAccount($user,$userData)
	{	

		$scope = $this->scopes;

		$oauth = OAuthAccount::firstOrCreate(
						array(
							'user_id' => $user->id, 
							'provider' => 'facebook'
						));

		$oauth->access_token = $userData['access_token'];
		$oauth->uid = $userData['uid'];
		$oauth->location = $userData['location'];
		$oauth->url = $userData['url'];
		$oauth->username = $userData['username'];

		if(isset($userData['image_url']))
		{
			$oauth->image_url = $userData['image_url'];
		}

		if(!is_array($scope))
		{
			$scope = (array) $scope;
		}

		$scopes = array();

		foreach($scope as $s)
		{

			$scopes['facebook.'.$s] = 1;

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

		$config = Config::get('connect::facebook.clients.'.$this->client);

		$helper = new LaravelFacebookRedirectLoginHelper($config['redirect_uri']);

		return $helper->getLoginUrl($this->getScopes());
	}



}