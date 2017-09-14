<?php

namespace kaoken\LaravelConfirmation\Mail;

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
        return $this->text('vendor.mail.text.confirmation')
            ->subject(__('confirmation.email_registration_subject'))
            ->from(env('CONFIRMATION_FROM_EMAIL',$this->model->email), $this->model->name)
            ->with($this->model);
    }
}
