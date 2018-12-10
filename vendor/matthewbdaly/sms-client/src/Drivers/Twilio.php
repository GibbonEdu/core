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
 * Driver for Twilio.
 */
class Twilio implements Driver
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
     * Account ID.
     *
     * @var
     */
    private $accountId;

    /**
     * API Token.
     *
     * @var
     */
    private $apiToken;

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
        if (! array_key_exists('account_id', $config) || ! array_key_exists('api_token', $config)) {
            throw new DriverNotConfiguredException();
        }
        $this->accountId = $config['account_id'];
        $this->apiToken = $config['api_token'];
    }

    /**
     * Get driver name.
     *
     * @return string
     */
    public function getDriver(): string
    {
        return 'Twilio';
    }

    /**
     * Get endpoint URL.
     *
     * @return string
     */
    public function getEndpoint(): string
    {
        return "https://api.twilio.com/2010-04-01/Accounts/$this->accountId/Messages.json";
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
            $cleanMessage = [];
            $cleanMessage['To'] = $message['to'];
            $cleanMessage['From'] = $message['from'];
            $cleanMessage['Body'] = $message['content'];
            $response = $this->client->request('POST', $this->getEndpoint(), [
                'form_params' => $cleanMessage,
                'auth' => [
                    $this->accountId,
                    $this->apiToken
                ]]);
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
