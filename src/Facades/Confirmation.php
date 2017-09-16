<?php

namespace Kaoken\LaravelConfirmation\Facades;

use Illuminate\Support\Facades\Facade;

class Confirmation extends Facade
{
    /**
     * User already exists.
     *
     * @var string
     */
    const USER_FIND = 'confirmations.find';

    /**
     * A constant that represents a notification sent successfully.
     *
     * @var string
     */
    const CONFIRMATION_LINK_SENT = 'confirmations.sent';

    /**
     * Confirmation of constant indicating that registration was successful.
     *
     * @var string
     */
    const REGISTRATION = 'confirmations.register';

    /**
     * Invalid user
     *
     * @var string
     */
    const INVALID_USER = 'confirmations.user';

    /**
     * Constant representing an invalid confirmation.
     *
     * @var string
     */
    const INVALID_CONFIRMATION = 'confirmations.confirmation';

    /**
     * Constant representing an invalid token.
     *
     * @var string
     */
    const INVALID_TOKEN = 'confirmations.token';

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'auth.kaoken.confirmation';
    }
}
