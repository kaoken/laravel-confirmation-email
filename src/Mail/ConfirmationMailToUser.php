<?php

namespace Kaoken\LaravelConfirmation\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ConfirmationMailToUser extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var object
     */
    protected $user;
    /**
     * token
     * @var string
     */
    protected $token;
    /**
     * Completely registered URL
     * @var string
     */
    protected $registerUrl;

    /**
     * Create a new message instance.
     *
     * @param object $user User model derived from `Model` class
     * @param string $token token
     */
    public function __construct($user, string $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    /**
     * Build the message.
     * @return $this
     */
    public function build()
    {
        $m = $this->text('vendor.confirmation.mail.confirmation')
            ->subject(__('confirmation.email_confirmation_subject'))
            ->to($this->user->email, $this->user->name)
            ->with(['user'=>$this->user, 'token'=>$this->token]);

        return $m;
    }
}
