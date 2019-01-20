<?php

namespace spec\Matthewbdaly\SMS\Drivers;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface as GuzzleInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Http\Message\ResponseInterface;
use Matthewbdaly\SMS\Drivers\Twilio;
use PhpSpec\ObjectBehavior;

class TwilioSpec extends ObjectBehavior
{
    public function let(GuzzleInterface $client, ResponseInterface $response)
    {
        $config = [
            'account_id' => 'MY_TWILIO_ACCOUNT_ID',
            'api_token' => 'MY_TWILIO_API_TOKEN',
        ];
        $this->beConstructedWith($client, $response, $config);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Twilio::class);
    }

    public function it_implements_interface()
    {
        $this->shouldImplement('Matthewbdaly\SMS\Contracts\Driver');
    }

    public function it_throws_exception_if_account_name_not_configured(GuzzleInterface $client, ResponseInterface $response)
    {
        $config = [
            'account_id' => 'MY_TWILIO_ACCOUNT_ID',
        ];
        $this->beConstructedWith($client, $response, $config);
        $this->shouldThrow('Matthewbdaly\SMS\Exceptions\DriverNotConfiguredException')->during('__construct', [$client, $response, $config]);
    }

    public function it_throws_exception_if_api_token_not_configured(GuzzleInterface $client, ResponseInterface $response)
    {
        $config = [
            'api_token' => 'MY_TWILIO_API_TOKEN',
        ];
        $this->beConstructedWith($client, $response, $config);
        $this->shouldThrow('Matthewbdaly\SMS\Exceptions\DriverNotConfiguredException')->during('__construct', [$client, $response, $config]);
    }

    public function it_returns_the_driver_name()
    {
        $this->getDriver()->shouldReturn('Twilio');
    }

    public function it_returns_the_driver_endpoint()
    {
        $this->getEndpoint()->shouldReturn('https://api.twilio.com/2010-04-01/Accounts/MY_TWILIO_ACCOUNT_ID/Messages.json');
    }

    public function it_sends_the_request(ResponseInterface $response)
    {
        $msg = [
            'to'      => '+44 01234 567890',
            'from'    => '+44 01234 567890',
            'content' => 'Just testing',
        ];
        $mock = new MockHandler(
            [
            new GuzzleResponse(201),
            ]
        );
        $handler = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $handler]);
        $config = [
            'account_id' => 'MY_TWILIO_ACCOUNT_ID',
            'api_token' => 'MY_TWILIO_API_TOKEN',
        ];
        $this->beConstructedWith($client, $response, $config);
        $this->sendRequest($msg)->shouldReturn(true);
    }

    public function it_throws_an_error_for_400(ResponseInterface $response)
    {
        $msg = [
            'to'      => '+44 01234 567890',
            'from'    => '+44 01234 567890',
            'content' => 'Just testing',
        ];
        $mock = new MockHandler(
            [
            new \GuzzleHttp\Exception\ClientException("", new Request('POST', 'test'))
            ]
        );
        $handler = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $handler]);
        $config = [
            'account_id' => 'MY_TWILIO_ACCOUNT_ID',
            'api_token' => 'MY_TWILIO_API_TOKEN',
        ];
        $this->beConstructedWith($client, $response, $config);
        $this->shouldThrow('Matthewbdaly\SMS\Exceptions\ClientException')->during('sendRequest', [$msg]);
    }

    public function it_throws_an_error_for_500(ResponseInterface $response)
    {
        $msg = [
            'to'      => '+44 01234 567890',
            'from'    => '+44 01234 567890',
            'content' => 'Just testing',
        ];
        $mock = new MockHandler(
            [
            new \GuzzleHttp\Exception\ServerException("", new Request('POST', 'test'))
            ]
        );
        $handler = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $handler]);
        $config = [
            'account_id' => 'MY_TWILIO_ACCOUNT_ID',
            'api_token' => 'MY_TWILIO_API_TOKEN',
        ];
        $this->beConstructedWith($client, $response, $config);
        $this->shouldThrow('Matthewbdaly\SMS\Exceptions\ServerException')->during('sendRequest', [$msg]);
    }

    public function it_throws_an_error_for_request_exception(ResponseInterface $response)
    {
        $msg = [
            'to'      => '+44 01234 567890',
            'from'    => '+44 01234 567890',
            'content' => 'Just testing',
        ];
        $mock = new MockHandler(
            [
            new \GuzzleHttp\Exception\RequestException("", new Request('POST', 'test'))
            ]
        );
        $handler = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $handler]);
        $config = [
            'account_id' => 'MY_TWILIO_ACCOUNT_ID',
            'api_token' => 'MY_TWILIO_API_TOKEN',
        ];
        $this->beConstructedWith($client, $response, $config);
        $this->shouldThrow('Matthewbdaly\SMS\Exceptions\RequestException')->during('sendRequest', [$msg]);
    }

    public function it_throws_an_error_for_connect_exception(ResponseInterface $response)
    {
        $msg = [
            'to'      => '+44 01234 567890',
            'from'    => '+44 01234 567890',
            'content' => 'Just testing',
        ];
        $mock = new MockHandler(
            [
            new \GuzzleHttp\Exception\ConnectException("", new Request('POST', 'test'))
            ]
        );
        $handler = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $handler]);
        $config = [
            'account_id' => 'MY_TWILIO_ACCOUNT_ID',
            'api_token' => 'MY_TWILIO_API_TOKEN',
        ];
        $this->beConstructedWith($client, $response, $config);
        $this->shouldThrow('Matthewbdaly\SMS\Exceptions\ConnectException')->during('sendRequest', [$msg]);
    }
}
