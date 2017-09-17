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
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        $all = $request->all();

        $model = $this->guard()->getProvider()->getModel();
        $tbl = (new $model())->getTable();
        if( !Confirmation::broker($tbl)->confirmed($all['email']) ) return false;

        return $this->guard()->attempt( $this->credentials($request), $request->has('remember')) ;
    }
}
