<?php
declare(strict_types=1);

namespace Matthewbdaly\SMS\Drivers;

use Psr\Log\LoggerInterface;
use Matthewbdaly\SMS\Contracts\Driver;

/**
 * Driver for Clockwork.
 */
class Log implements Driver
{
    /**
     * Logger.
     *
     * @var
     */
    private $logger;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger The logger instance.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get driver name.
     *
     * @return string
     */
    public function getDriver(): string
    {
        return 'Log';
    }

    /**
     * Get endpoint URL.
     *
     * @return string
     */
    public function getEndpoint(): string
    {
        return '';
    }

    /**
     * Send the request.
     *
     * @param array $message An array containing the message.
     *
     * @return boolean
     */
    public function sendRequest(array $message): bool
    {
        $this->logger->info('Message sent', $message);
        return true;
    }
}
