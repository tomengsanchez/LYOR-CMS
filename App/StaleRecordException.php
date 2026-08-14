<?php
namespace App;

/**
 * Raised when optimistic locking detects another user saved the same row first.
 */
class StaleRecordException extends \RuntimeException
{
    public function __construct(string $message = 'This record was updated by someone else. Please reload and try again.')
    {
        parent::__construct($message);
    }
}
