<?php

namespace Kaoken\LaravelConfirmation;

use DB;
use Closure;
use Illuminate\Mail\PendingMail;

class ConfirmationBroker implements IConfirmationBroker
{
    /**
     * Confirmation App config.
     *
     * @var array
     */
    protected $config;
    /**
     * ConfirmationDB instance.
     *
     * @var \Kaoken\LaravelConfirmation\ConfirmationDB
     */
    protected $db;
    /**
     * Middle path of URL
     *
     * @var string
     */
    protected $path;
    /**
     * User model
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * Mailer instance.
     *
     * @var \Illuminate\Contracts\Mail\Mailer
     */
    protected $mailer;

    /**
     * It is a confirmation mail link.
     *
     * @var string
     */
    protected $emailConfirmationClass;

    /**
     * It is a registration mail.
     *
     * @var string
     */
    protected $emailRegistrationClass;

    /**
     * Create a new confirmation broker instance.
     * @param  array $config
     * @param  ConfirmationDB $db
     * @param  $model
     * @param  string $path
     * @param  PendingMail  $mailer
     * @param  string  $emailConfirmationClass
     * @param  string  $emailConfirmationClass
     */
    public function __construct(
        array $config,
        ConfirmationDB $db,
        $model,
        string $path,
        PendingMail $mailer,
        string $emailConfirmationClass,
        string  $emailRegistrationClass)
    {
        $this->config = $config;
        $this->db = $db;
        $this->path = $path;
        $this->model = $model;
        $this->mailer = $mailer;
        $this->emailConfirmationClass = $emailConfirmationClass;
        $this->emailRegistrationClass = $emailRegistrationClass;
    }

    /**
     * We will send a confirmation link to the user.
     *
     * @param  array  $all
     * @return string
     */
    public function createUserAndSendConfirmationLink(array $all)
    {
        switch (($token = $this->db->create($all))) {
            case static::USER_FIND:
            case static::INVALID_USER:
            case static::INVALID_CONFIRMATION:
            return $token;
        }

        $this->emailConfirmationLink($this->db->getUser($all['email']), $token);

        return static::CONFIRMATION_LINK_SENT;
    }


    /**
     * Send the link in the confirmation reset by e-mail.
     *
     * @param  $user
     * @param  string  $token
     * @return void
     */
    public function emailConfirmationLink($user, $token)
    {
        $class = $this->emailConfirmationClass;
        $this->mailer->send(new $class($user, $token,url($this->path.urlencode($user->email)."/".$token)));
    }

    /**
     * Is there a combination of the specified email address and token?
     * @param string $email mail address
     * @param string $token token
     * @return bool Returns true if it exists.
     */
    public function checkEMailToken($email, $token)
    {
        return $this->db->checkEMailToken($email, $token);
    }

    /**
     * Delete the record of the token and perform Complete registration work.
     * @param string $email mail address
     * @param string $token token
     * @return bool Returns true if it exists.
     */
    public function registration($email, $token)
    {
        if( $this->db->registration($email, $token) != static::REGISTRATION ){
            return static::INVALID_CONFIRMATION;
        }

        $this->emailRegistration($this->db->getUser($email));

        return static::REGISTRATION;
    }

    /**
     * Notify of registration by e-mail.
     *
     * @param  $user
     * @param  \Closure|null  $callback
     * @return int
     */
    protected function emailRegistration($user, Closure $callback = null)
    {
        $class = $this->emailRegistrationClass;
        $this->mailer->send(new $class($user));
    }

    /**
     * Delete Auth users and tokens that are pre-registered and have expired.
     * @return int Number of deleted users.
     */
    public function deleteUserAndToken()
    {
        return $this->db->deleteUserAndToken();
    }


    /**
     * Is it an authenticated email address?
     * @param string $email
     * @return bool Returns true if it exists.
     */
    public function authenticated($email)
    {
        return !$this->db->existenceEmail($email);
    }

}
