<?php

namespace Kaoken\LaravelConfirmation\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class RegistrationMailToUser extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var object
     */
    protected $model;

    /**
     * Create a new message instance.
     *
     * @param object $model User model derived from `Model` class
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Build the message.
     * @return $this
     */
    public function build()
    {
        $m= $this->text('vendor.confirmation.mail.registration')
            ->subject(__('confirmation.email_registration_subject'))
            ->to($this->model->email, $this->model->name)
            ->with('user',$this->model);

        if( filter_var(env('CONFIRMATION_FROM_EMAIL'), FILTER_VALIDATE_EMAIL))
            $m->from(env('CONFIRMATION_FROM_EMAIL',''), env('CONFIRMATION_FROM_NAME','webmaster'));
        return $m;
    }
}
