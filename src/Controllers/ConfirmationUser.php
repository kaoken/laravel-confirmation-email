<?php
/**
 *
 */
namespace Kaoken\LaravelConfirmation\Controllers;

use Confirmation;
use Illuminate\Http\Request;

trait ConfirmationUser
{
    /**
     * Create 1st registered user and send confirmation email.
     * @param array $data
     * @return bool
     */
    protected function createUserAndSendConfirmationLink(array $data)
    {
        $response = Confirmation::broker($this->broker)
            ->createUserAndSendConfirmationLink($data);
        if ($response != Confirmation::CONFIRMATION_LINK_SENT) {
            return false;
        }
        return true;
    }

    /**
     * complete registration view name
     * @see ConfirmationUser::getCompleteRegistration
     * @return string
     */
    protected function completeRegistrationView()
    {
        return 'vendor.confirmation.registration';
    }

    /**
     * View name when no registered user exists or is registered
     * @see ConfirmationUser::getCompleteRegistration
     * @return string
     */
    protected function registration404View()
    {
        return '404';
    }

    /**
     * complete registration process
     * @param Request $request
     * @param string $email
     * @param string $token
     * @return \Illuminate\Http\Response
     */
    public function getCompleteRegistration(Request $request, $email, $token)
    {
        if( !($email == "" || $token == "") ){
            /**
             * @var \Kaoken\LaravelConfirmation\ConfirmationBroker
             */
            $obj = Confirmation::broker($this->broker);
            switch ($obj->registration($email, $token)){
                case Confirmation::REGISTRATION:
                    return response()->view($this->completeRegistrationView());
            }
        }
        // For combinations of tokens and e-mails already confirmed or not present, 404.
        return response()->view($this->registration404View(), [], 404);
    }
}