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
    protected $model;
    /**
     * token
     * @var string
     */
    protected $token;
    /**
     * token
     * @var string
     */
    protected $registerUrl;

    /**
     * Create a new message instance.
     *
     * @param object $model User model derived from `Model` class
     * @param string $token token
     * @param string $registerUrl URL
     */
    public function __construct($model, string $token, string $registerUrl)
    {
        $this->model = $model;
        $this->token = $token;
        $this->registerUrl = $registerUrl;
    }

    /**
     * Build the message.
     * @return $this
     */
    public function build()
    {
        return $this->text('vendor.confirmation.mail.text.confirmation')
            ->subject(__('confirmation.email_registration_subject'))
            ->from(env('CONFIRMATION_FROM_EMAIL',''), $this->model->name)
            ->with(['user'=>$this->model, 'token'=>$this->token, 'registerUrl'=>$this->registerUrl]);
    }
}
