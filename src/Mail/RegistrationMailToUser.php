<?php

namespace Kaoken\LaravelConfirmation\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationMailToUser extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var object
     */
    protected $user;

    /**
     * Create a new message instance.
     *
     * @param object $user User model derived from `Model` class
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     * @return $this
     */
    public function build()
    {
        $m= $this->text('vendor.confirmation.mail.registration')
            ->subject(__('confirmation.email_registration_subject'))
            ->to($this->user->email, $this->user->name)
            ->with('user',$this->user);
        return $m;
    }
}
