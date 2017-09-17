<?php
/**
 * Called after Auth user  is complete registered.
 */
namespace Kaoken\LaravelConfirmation\Events;

use Illuminate\Queue\SerializesModels;

class RegistrationEvent
{
    use SerializesModels;
    /**
     * Auth user Model
     * @var object
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param $user Auth user Model
     */
    public function __construct($user)
    {
        $this->user = $user;
    }
}
