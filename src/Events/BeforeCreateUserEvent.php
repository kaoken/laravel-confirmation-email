<?php
/**
 * Called before the user is created.
 * @warning When this event is invoked, a DB transaction related to Auth user creation is in progress.
 * When creating an exception with the listener, the creation of the target Auth user is rolled back immediately.
 */
namespace Kaoken\LaravelConfirmation\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class BeforeCreateUserEvent
{
    use SerializesModels;
    /**
     * Auth user items data
     * @var array
     */
    public $data;

    /**
     * Create a new event instance.
     *
     * @param array $data Auth user items data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }
}