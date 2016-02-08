<?php namespace Rjvim\Connect\Providers;

use Config;
use Redirect;
use Request;
use Rjvim\Connect\Models\OAuthAccount;
use Guzzle\Service\Client as GuzzleClient;
use Guzzle\Http\Exception\BadResponseException;


class Github implements ProviderInterface{


	protected $client;
	protected $scopes;

	/**
	 * Constructor for Connect Library
	 */
	public function __construct($client, $scope)
	{
		$this->scopes = $scope;

		$config = Config::get('connect::github.clients.'.$client);

		if(is_array($scope))
		{
			$scopes = array();

			foreach($scope as $s)
			{
				$scopes = array_merge(Config::get('connect::github.scopes.'.$s),$scopes);
			}
		}
		else
		{
			$scopes = Config::get('connect::github.scopes.'.$scope);
		}

		$this->client= new \League\OAuth2\Client\Provider\Github(array(
		    'clientId'  =>  $config['client_id'],
		    'clientSecret'  =>  $config['client_secret'],
		    'redirectUri'   =>  $config['redirect_uri'],
		    'scopes' => $scopes
		));
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
		$sentry = \App::make('sentry');

		try
		{

			$user = $sentry->findUserByLogin($email);

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

		$token = $this->client->getAccessToken('authorizationCode', [
	        'code' => $req->get('code')
	    ]);

	    $github = new \Github\Client();

	    $github->authenticate($token->accessToken,'http_token');

	    $response = $github->getHttpClient()->get('user');

	    $user = \Github\HttpClient\Message\ResponseMediator::getContent($response);

	    $verifiedEmails = $this->getVerifiedEmails($token->accessToken); 

	    $email = $this->getPrimaryEmail($verifiedEmails);

		$result['uid'] = $user['id'];
		$result['email'] = $email;
		$result['username'] = $user['login'];
		$result['url'] = $user['html_url'];
		$result['location'] = isset($user['location']) ? $user['location'] : null;

		$fresult = $this->findUser($result['email']);

		if($fresult['found'])
		{
			$fuser = $fresult['user'];

			$result['first_name'] = $fuser->first_name;
			$result['last_name'] = $fuser->last_name;
		}
		else
		{
			if(isset($user['name']))
			{
				$name = explode(' ', $user['name']);
				$result['first_name'] = $name[0];
				$result['last_name'] = isset($name[1]) ? $name[1] : $name[0];
			}
			else
			{
				$result['first_name'] = $result['last_name'] = $result['username'];
			}
		}

		$result['access_token'] = $token->accessToken;

		return $result;

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
							'provider' => 'github'
						));

		$oauth->access_token = $userData['access_token'];
		$oauth->username = $userData['username'];
		$oauth->uid = $userData['uid'];
		$oauth->location = $userData['location'];
		$oauth->url = $userData['url'];

		if(!is_array($scope))
		{
			$scope = (array) $scope;
		}

		$scopes = array();

		foreach($scope as $s)
		{

			$scopes['github.'.$s] = 1;

		}

		$oauth->scopes = $scopes;

		$oauth->save();

		return true;

	}

    /**
     * Get the primary, verified email address from the Github data.
     *
     * @param  mixed $emails
     * @return mixed
     */
    protected function getPrimaryEmail($emails)
    {
        foreach ($emails as $email) {
            if (! $email->primary) {
                continue;
            }

            if ($email->verified) {
                return $email->email;
            }

            throw new GithubEmailNotVerifiedException;
        }

        return null;
    }

    /**
     * Get all the users email addresses
     *
     * @param  string $token
     * @return mixed
     */
    protected function getVerifiedEmails($token)
    {
        $url = 'https://api.github.com/user/emails?access_token='.$token;

        try {

            $client = new GuzzleClient;
            $client->setBaseUrl($url);

            $request = $client->get()->send();
            $response = $request->getBody();

        } catch (BadResponseException $e) {
            // @codeCoverageIgnoreStart
            $raw_response = explode("\n", $e->getResponse());
            d($raw_response); die;
        }

        return json_decode($response);
    }

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function getAuthUrl()
	{

		return $this->client->getAuthorizationUrl();
	}


}