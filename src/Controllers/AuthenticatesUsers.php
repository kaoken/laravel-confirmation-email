<?php

namespace Kaoken\LaravelConfirmation\Controllers;

use DB;
use Confirmation;
use Illuminate\Http\Request;

/**
 * Trait AuthenticatesUsers
 * @see \Illuminate\Foundation\Auth\AuthenticatesUsers
 * @package Kaoken\LaravelConfirmation\Controllers
 */
trait AuthenticatesUsers
{
    use \Illuminate\Foundation\Auth\AuthenticatesUsers;

    /**
     * Get the Auth user model class
     * @return string
     */
    abstract protected function getAuthUserClass();
    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        $all = $request->all();
        $class = $this->getUserClass();

        $model = $this->guard()->getProvider()->getModel();
        $user = DB::table($model->getTable())->where('email',$all['email']);
        if( is_null($user) ) return false;

        if( !Confirmation::broker($this->broker)->authenticated($user->email) ) return false;

        return $this->guard()->attempt( $this->credentials($request), $request->has('remember')) ;
    }
}
