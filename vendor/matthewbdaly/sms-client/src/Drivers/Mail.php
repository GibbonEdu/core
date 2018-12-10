<?php
declare(strict_types=1);

namespace Matthewbdaly\SMS\Drivers;

use Matthewbdaly\SMS\Contracts\Mailer;
use Matthewbdaly\SMS\Contracts\Driver;
use Matthewbdaly\SMS\Exceptions\DriverNotConfiguredException;

/**
 * Generic mail driver
 */
class Mail implements Driver
{
    /**
     * Mailer.
     *
     * @var
     */
    protected $mailer;

    /**
     * Endpoint.
     *
     * @var
     */
    protected $endpoint;

    /**
     * Constructor.
     *
     * @param Mailer $mailer The Mailer instance.
     * @param array  $config The configuration.
     * @throws DriverNotConfiguredException Driver not configured correctly.
     *
     * @return void
     */
    public function __construct(Mailer $mailer, array $config)
    {
        $this->mailer = $mailer;
        if (! array_key_exists('domain', $config)) {
            throw new DriverNotConfiguredException();
        }
        $this->endpoint = $config['domain'];
    }

    /**
     * Get driver name.
     *
     * @return string
     */
    public function getDriver(): string
    {
        return 'Mail';
    }

    /**
     * Get endpoint domain.
     *
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * Send the SMS.
     *
     * @param array $message An array containing the message.
     *
     * @return boolean
     */
    public function sendRequest(array $message): bool
    {
        try {
            $recipient = preg_replace('/\s+/', '', $message['to']) . "@" . $this->endpoint;
            $this->mailer->send($recipient, $message['content']);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
