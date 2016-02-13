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

		$config = Config::get('rjvim.connect.facebook.clients.'.$client);

		FacebookSession::setDefaultApplication($config['client_id'], $config['client_secret']);
	}

	public function getScopes()
	{

		if(is_array($this->scopes))
		{
			$scopes = array();

			foreach($this->scopes as $s)
			{
				$scopes = array_merge(Config::get('rjvim.connect.facebook.scopes.'.$s),$scopes);
			}
		}
		else
		{
			$scopes = Config::get('rjvim.connect.facebook.scopes.'.$this->scopes);
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

		$config = Config::get('rjvim.connect.facebook.clients.'.$this->client);

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
		      $session, 'GET', '/me?fields=id,name,email,birthday,location,gender,bio,link'
		    ))->execute()->getGraphObject(GraphUser::className());

		    // dd($user_profile);

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

		  	$user_profile = $user_profile->asArray();

		  	$result = [];

			$result['uid'] = isset($user_profile['id']) ? $user_profile['id'] : '';
			$result['name'] = isset($user_profile['name']) ? $user_profile['name'] : '';
			$result['gender'] = isset($user_profile['gender']) ? $user_profile['gender'] : '';
			$result['birthday'] = isset($user_profile['birthday']) ? $user_profile['birthday'] : '';
			$result['link'] = isset($user_profile['link']) ? $user_profile['link'] : '';

			if($this->sentry->check())
			{
				$result['email'] = $this->sentry->getUser()->email;
			}
			else
			{
				$result['email'] = $user_profile['email'];
			}

			$result['username'] = $result['name'];

			$result['location'] = isset($user_profile['location']->name) ? $user_profile['location']->name : '';

			$fresult = $this->findUser($result['email']);

			if($fresult['found'])
			{
				$fuser = $fresult['user'];

				$result['name'] = $fuser->name;
			}
			else
			{
				$result['name'] = $result['name'];
			}

			
			$result['access_token'] = $session->getLongLivedSession()->getToken();

			if(!$user_image['is_silhouette'])
			{
				$result['image_url'] = $user_image['url'];
			}

			$result['image'] = $user_image['url'];

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
		$oauth->username = $userData['username'];
		$oauth->gender = $userData['gender'];
		$oauth->birthday = $userData['birthday'];
		$oauth->location = $userData['location'];

		if(isset($userData['image']))
		{
			$oauth->image_url = $userData['image'];
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
		$config = Config::get('rjvim.connect.facebook.clients.'.$this->client);

		$helper = new LaravelFacebookRedirectLoginHelper($config['redirect_uri'],$config['client_id'], $config['client_secret']);

		return $helper->getLoginUrl($this->getScopes());
	}



}