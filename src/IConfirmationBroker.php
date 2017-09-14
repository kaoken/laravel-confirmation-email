<?php

namespace kaoken\LaravelConfirmation;

use Closure;

interface IConfirmationBroker
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
     * Send a confirmation reset link to a user.
     *
     * @param  array  $all
     * @param  \Closure|null  $callback
     * @return string
     */
    public function createUserAndSendConfirmationLink(array $all, Closure $callback = null);

    /**
     * 本登録をする
     * @param string $email mail address
     * @param string $token token
     * @param  \Closure|null  $callback
     * @return bool Returns true if it exists.
     */
    public function registration($email, $token, Closure $callback = null);

    /**
     * Delete Auth users and tokens that are pre-registered and have expired.
     * @return int Number of deleted users.
     */
    public function deleteUserAndToken();

    /**
     * Is it an authenticated email address?
     * @param string $email
     * @return bool Returns true if it exists.
     */
    public function authenticated($email);
}