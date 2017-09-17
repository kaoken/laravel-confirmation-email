<?php
/**
 * Event to be called before deleting expired users.
 * @warning When this event is invoked, a DB transaction related to Auth user delete is in progress.
 * When creating an exception with the listener, the delete of the target Auth user is rolled back immediately.
 * This is called only if you `deleteUserAndToken(true)` the `Confirmation::broker('hoge')->deleteUserAndToken();`
 * method argument to `true`.
 */
namespace Kaoken\LaravelConfirmation\Events;

use Illuminate\Queue\SerializesModels;
use \Illuminate\Support\Collection;

class BeforeDeleteUsersEvent
{
    use SerializesModels;
    /**
     * Auth user models data
     * @var Collection
     */
    public $data;

    /**
     * Create a new event instance.
     *
     * @param Collection $data Auth user models data
     */
    public function __construct(Collection $data)
    {
        $this->data = $data;
    }
}