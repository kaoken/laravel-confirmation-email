<?php

namespace kaoken\LaravelConfirmation;

use DB;
use Closure;
use UnexpectedValueException;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use App\Library\Auth\Confirmation\CanConfirmation as CanConfirmationContract;

class ConfirmationBroker implements IConfirmationBroker
{

    /**
     * ConfirmationDB instance.
     *
     * @var \kaoken\LaravelConfirmation\ConfirmationDB
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
    protected $emailConfirmationView;

    /**
     * It is a registration mail.
     *
     * @var string
     */
    protected $emailRegistrationView;

    /**
     * Create a new confirmation broker instance.
     * @param  ConfirmationDB $db
     * @param  $model
     * @param  string $path
     * @param  \Illuminate\Contracts\Mail\Mailer  $mailer
     * @param  string  $emailConfirmationView
     * @param  string  $emailConfirmationView
     */
    public function __construct(
        ConfirmationDB $db,
        $model,
        $path,
        MailerContract $mailer,
        $emailConfirmationView,
        $emailRegistrationView)
    {
        $this->db = $db;
        $this->path = $path;
        $this->model = $model;
        $this->mailer = $mailer;
        $this->emailConfirmationView = $emailConfirmationView;
        $this->emailRegistrationView = $emailRegistrationView;
    }

    /**
     * We will send a confirmation link to the user.
     *
     * @param  array  $all
     * @param  \Closure|null  $callback
     * @return string
     */
    public function createUserAndSendConfirmationLink(array $all, Closure $callback = null)
    {
        switch (($token = $this->db->create($all))) {
            case static::USER_FIND:
            case static::INVALID_USER:
            case static::INVALID_CONFIRMATION:
            return $token;
        }

        $this->emailConfirmationLink($this->db->getUser($all['email']), $token, $callback);

        return static::CONFIRMATION_LINK_SENT;
    }


    /**
     * Send the link in the confirmation reset by e-mail.
     *
     * @param  $user
     * @param  string  $token
     * @param  \Closure|null  $callback
     * @return void
     */
    public function emailConfirmationLink($user, $token, Closure $callback = null)
    {
        $view = $this->emailConfirmationView;
        $mailData = [
            'user' => $user,
            'token' => $token,
            'register_url' => url($this->path.urlencode($user->email)."/".$token)
        ];

        $this->mailer->send($view, $mailData, function ($m) use ($user, $token, $callback) {
            $m->to($user->email);

            if (! is_null($callback)) {
                call_user_func($callback, $m, $user, $token);
            }
        });
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
     * registration
     * @param string $email mail address
     * @param string $token token
     * @param  \Closure|null  $callback
     * @return bool Returns true if it exists.
     */
    public function registration($email, $token, Closure $callback = null)
    {
        if( $this->db->registration($email, $token) != static::REGISTRATION ){
            return static::INVALID_CONFIRMATION;
        }

        $this->emailRegistration($this->db->getUser($email), $callback);

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
        $view = $this->emailRegistrationView;
        $mailData = [
            'user' => $user
        ];

        return $this->mailer->send($view, $mailData, function ($m) use ($user, $callback) {
            $m->to($user->email);

            if (! is_null($callback)) {
                call_user_func($callback, $m, $user);
            }
        });
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
