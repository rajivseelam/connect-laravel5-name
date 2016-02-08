<?php namespace Rjvim\Connect\Models;

class OAuthAccount extends \Eloquent {


	protected $table = "oauth_accounts";

	protected $fillable = array('user_id','provider');

	protected $allowedPermissionsValues = array(-1, 0, 1);
	
	/**
	 * Relations
	 */
	public function user()
	{
		return $this->belongsTo('Rjvim\Connect\Models\User');
	}

	/**
	 * Returns permissions for the contact.
	 *
	 * @return array
	 */
	public function getScopes()
	{
		return $this->scopes;
	}

	/**
	 * Mutator for giving permissions.
	 *
	 * @param  mixed  $permissions
	 * @return array  $_permissions
	 */
	public function getScopesAttribute($scopes)
	{
		if ( ! $scopes)
		{
			return array();
		}

		if (is_array($scopes))
		{
			return $scopes;
		}

		if ( ! $_scopes = json_decode($scopes, true))
		{
			throw new \InvalidArgumentException("Cannot JSON decode scopes [$scopes].");
		}

		return $_scopes;
	}

		/**
	 * Mutator for taking permissions.
	 *
	 * @param  array  $permissions
	 * @return string
	 */
	public function setScopesAttribute(array $scopes)
	{
		// Merge permissions
		//$scopes = array_merge($this->getScopes(), $scopes);

		// Loop through and adjust permissions as needed
		foreach ($scopes as $scope => &$value)
		{
			// Lets make sure there is a valid permission value
			if ( ! in_array($value = (int) $value, $this->allowedPermissionsValues))
			{
				throw new \InvalidArgumentException("Invalid value [$value] for permission [$permission] given.");
			}

			// If the value is 0, delete it
			if ($value === 0)
			{
				unset($scopes[$scope]);
			}
		}

		$this->attributes['scopes'] = ( ! empty($scopes)) ? json_encode($scopes) : '';
	}

	public function hasAccess($scopes)
	{
		$this->user->setMorePermissions($this->getScopes());

		$result = $this->user->hasAccess($scopes);

		return $result;
	}



}