<?php
/**
 * Use with Auth user model
 */
namespace Kaoken\LaravelConfirmation;

use Confirmation;

trait HasConfirmation
{
    /**
     * Is it an confirmed email address?
     * @return bool If true, it is confirmed.
     */
    protected function confirmed()
    {
        if( !filter_var($this->email, FILTER_VALIDATE_EMAIL))
            return false;
        return Confirmation::broker($this->getTable())->confirmed($this->email);
    }
}