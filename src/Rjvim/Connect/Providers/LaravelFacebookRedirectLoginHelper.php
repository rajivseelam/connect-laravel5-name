<?php namespace Rjvim\Connect\Providers;

use Session;

class LaravelFacebookRedirectLoginHelper extends \Facebook\FacebookRedirectLoginHelper {


	protected function storeState($state)
    {
        Session::put('facebook.state', $state);
    }

    protected function loadState()
    {
        return $this->state =  Session::get('facebook.state');
    }

}