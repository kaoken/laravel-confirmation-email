<?php
/**
 * An Auth user is created and called after sending the confirmation mail.
 */
namespace Kaoken\LaravelConfirmation\Events;

use Illuminate\Queue\SerializesModels;

class ConfirmationEvent
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
