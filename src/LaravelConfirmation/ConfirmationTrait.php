<?php
namespace kaoken\LaravelConfirmation;

use Confirmation;
use Exception;
use Illuminate\Mail\Message;
use \Illuminate\Http\Request;

/**
 * Trait ConfirmationTrait
 * @package kaoken\LaravelConfirmation
 */
trait ConfirmationTrait
{
    /**
     * Get Confirmation Email Subject
     * @return string
     */
    abstract function getConfirmationEmailSubject();
    /**
     * Get mail subject of main registration.
     * @return string
     */
    abstract function getRegistrationEmailSubject();
    /**
     * Create a registered user and send a confirmation email.
     * @param array $data
     * @return bool
     */
    protected function createUserAndSendConfirmationLink(array $data)
    {
        $response = Confirmation::broker($this->broker)
            ->createUserAndSendConfirmationLink($data, $this->confirmationEmailBuilder());
        if ($response != Confirmation::CONFIRMATION_LINK_SENT) {
            return false;
        }
        return true;
    }

    /**
     * Mail for confirmation user creation
     * @return \Closure
     */
    public function confirmationEmailBuilder()
    {
        return function (Message $message, $user, $token) {
            $message->subject($this->getConfirmationEmailSubject());
        };
    }

    /**
     * Mail for user creation after registration
     * @return \Closure
     */
    public function registrationEmailBuilder()
    {
        return function (Message $message, $user) {
            $message->subject($this->getRegistrationEmailSubject());
        };
    }

    /**
     * Called after recording
     * @param string $email
     */
    protected function afterRegistration($email)
    {

    }

    /**
     * registration process
     * @param Request $request
     * @param string $email
     * @param string $token
     * @return \Illuminate\Http\Response
     */
    public function getRegistration(Request $request, $email, $token)
    {
        if( !($email == "" || $token == "") ){
            /**
             * @var \App\Library\Auth\Confirmation\ConfirmationBroker
             */
            $obj = Confirmation::broker($this->broker);
            switch ($obj->registration($email, $token, $this->registrationEmailBuilder())){
                case Confirmation::REGISTRATION:
                    $this->afterRegistration($email);
                    return response()->view($this->registrationView());
            }
        }
        return response()->view('404', [], 404);
    }
}