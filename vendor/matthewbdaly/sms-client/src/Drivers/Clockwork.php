<?php
declare(strict_types=1);

namespace Matthewbdaly\SMS\Drivers;

use GuzzleHttp\ClientInterface as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Psr\Http\Message\ResponseInterface;
use Matthewbdaly\SMS\Contracts\Driver;
use Matthewbdaly\SMS\Exceptions\DriverNotConfiguredException;

/**
 * Driver for Clockwork.
 */
class Clockwork implements Driver
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
     * Endpoint.
     *
     * @var
     */
    private $endpoint = 'https://api.clockworksms.com/http/send.aspx';

    /**
     * API Key.
     *
     * @var
     */
    private $apiKey;

    /**
     * Constructor.
     *
     * @param GuzzleClient      $client   The Guzzle Client instance.
     * @param ResponseInterface $response The response instance.
     * @param array             $config   The configuration array.
     * @throws DriverNotConfiguredException Driver not configured correctly.
     *
     * @return void
     */
    public function __construct(GuzzleClient $client, ResponseInterface $response, array $config)
    {
        $this->client = $client;
        $this->response = $response;
        if (! array_key_exists('api_key', $config)) {
            throw new DriverNotConfiguredException();
        }
        $this->apiKey = $config['api_key'];
    }

    /**
     * Get driver name.
     *
     * @return string
     */
    public function getDriver(): string
    {
        return 'Clockwork';
    }

    /**
     * Get endpoint URL.
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
     * @throws \Matthewbdaly\SMS\Exceptions\ClientException  Client exception.
     * @throws \Matthewbdaly\SMS\Exceptions\ServerException  Server exception.
     * @throws \Matthewbdaly\SMS\Exceptions\RequestException Request exception.
     * @throws \Matthewbdaly\SMS\Exceptions\ConnectException Connect exception.
     *
     * @return boolean
     */
    public function sendRequest(array $message): bool
    {
        try {
            $message['key'] = $this->apiKey;
            $response = $this->client->request('POST', $this->getEndpoint().'?'.http_build_query($message));
        } catch (ClientException $e) {
            throw new \Matthewbdaly\SMS\Exceptions\ClientException();
        } catch (ServerException $e) {
            throw new \Matthewbdaly\SMS\Exceptions\ServerException();
        } catch (ConnectException $e) {
            throw new \Matthewbdaly\SMS\Exceptions\ConnectException();
        } catch (RequestException $e) {
            throw new \Matthewbdaly\SMS\Exceptions\RequestException();
        }

        return true;
    }
}
