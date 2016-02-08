<?php namespace Rjvim\Connect\Models;

use Cartalyst\Sentry\Users\Eloquent\User as SentryUserModel;

class User extends SentryUserModel{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'users';

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = array('password');

	protected $morePermissions = array();

	/**
	 * A user is linked to many oauth_accounts
	 *
	 * @return void
	 * @author 
	 **/
	public function oauthAccounts()
	{
		return $this->hasMany('Rjvim\Connect\Models\OAuthAccount');
	}

	/**
	 * Get associated google oauth account
	 *
	 * @return void
	 * @author 
	 **/
	public function googleAccount()
	{
		return $this->hasOne('Rjvim\Connect\Models\OAuthAccount')
					->where('provider','google');
	}

	/**
	 * Get associated facebook oauth account
	 *
	 * @return void
	 * @author 
	 **/
	public function facebookAccount()
	{
		return $this->hasOne('Rjvim\Connect\Models\OAuthAccount')
					->where('provider','facebook');
	}

	/**
	 * Get associated google oauth account
	 *
	 * @return void
	 * @author 
	 **/
	public function youtubeAccounts()
	{
		return $this->hasMany('Rjvim\Connect\Models\OAuthAccount')
					->where('provider','youtube');
	}



	/**
	 * Set morePermissions variable to that it can be used while getting merged permissions 
	 *
	 * @return void
	 * @author 
	 **/

    public function setMorePermissions($permissions)
    {
    	$this->morePermissions = $permissions;
    }

	/**
	 * Mutator for giving permissions.
	 *
	 * @param  mixed  $permissions
	 * @return array  $_permissions
	 */
	public function getMorePermissions()
	{
		if ( ! $this->morePermissions)
		{
			return array();
		}

		if (is_array($this->morePermissions))
		{
			return $this->morePermissions;
		}

		if ( ! $_permissions = json_decode($this->morePermissions, true))
		{
			throw new \InvalidArgumentException("Cannot JSON decode permissions [$permissions].");
		}

		return $_permissions;
	}


    /**
     * Function to override default implementation of getMergedPermissions.
     * 
     * Here we try to merge morePermissions variable into the 
     *
     * @return void
     * @author 
     **/

	public function getMergedPermissions()
	{
		$result = parent::getMergedPermissions();

		$result = array_merge($result,$this->getMorePermissions());

		return $result;
	}

}