<?php
declare(strict_types=1);

namespace Matthewbdaly\SMS\Drivers;

use GuzzleHttp\ClientInterface as GuzzleClient;
use Psr\Http\Message\ResponseInterface;
use Matthewbdaly\SMS\Contracts\Driver;

/**
 * Null driver for testing.
 */
class NullDriver implements Driver
{
    /**
     * Guzzle client.
     *
     * @var
     */
    protected $client;

    /**
     * Guzzle response.
     *
     * @var
     */
    protected $response;

    /**
     * Constructor.
     *
     * @param GuzzleClient      $client   The Guzzle Client instance.
     * @param ResponseInterface $response The response instance.
     *
     * @return void
     */
    public function __construct(GuzzleClient $client, ResponseInterface $response)
    {
        $this->client = $client;
        $this->response = $response;
    }

    /**
     * Get driver name.
     *
     * @return string
     */
    public function getDriver(): string
    {
        return 'Null';
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
     * Send the SMS.
     *
     * @param array $message An array containing the message.
     *
     * @return boolean
     */
    public function sendRequest(array $message): bool
    {
        return true;
    }
}
