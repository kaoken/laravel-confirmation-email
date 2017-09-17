<?php
/**
 * Called after Auth user is created.
 */
namespace Kaoken\LaravelConfirmation\Events;

use Illuminate\Queue\SerializesModels;

class CreatedUserEvent
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
     * @param object $user Auth user model
     */
    public function __construct($user)
    {
        $this->user = $user;
    }
}