<?php

namespace Gibbon\Install\Http\Exception;

/**
 * Installation permission exception
 *
 * Represents permission issue, probably hack attempt, in installer.
 * By default, the constructor will log internally with error_log()
 * with the internal reason.
 *
 * The returns of getMessage() is safe for end-user display.
 */
class ForbiddenException extends \Exception
{
    /**
     * Message for internal logging. Explains to system admin, who
     * has access to server error logs, why the action is forbidden.
     *
     * @var string
     */
    protected $reason;

    /**
     * Constructor of the exception.
     *
     * @param string  $reason       The internal reason why an action is forbidden. For server logging.
     * @param boolean $errorLogNow  Should the constructor run error_log immediately about it. Default: true.
     */
    public function __construct(string $reason = '', bool $errorLogNow = true)
    {

        if (function_exists('__')) {
            parent::__construct(__('Your request failed because you do not have access to this action.'));
        } else {
            parent::__construct('Your request failed because you do not have access to this action.');
        }
        if ($errorLogNow) {
            error_log('Forbidden: ' . $this->getReasonWithTrace());
        }
    }

    /**
     * Get the internal logging message.
     *
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Get the internal logging message with proper stack trace.
     *
     * @return string
     */
    public function getReasonWithTrace(): string
    {
        return $this->reason . "\n" . $this->getTraceAsString();
    }
}
