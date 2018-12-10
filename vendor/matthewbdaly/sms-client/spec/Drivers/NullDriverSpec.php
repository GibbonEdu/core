<?php

namespace spec\Matthewbdaly\SMS\Drivers;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface as GuzzleInterface;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Http\Message\ResponseInterface;
use Matthewbdaly\SMS\Drivers\NullDriver;
use PhpSpec\ObjectBehavior;

class NullDriverSpec extends ObjectBehavior
{
    public function let(GuzzleInterface $client, ResponseInterface $response)
    {
        $this->beConstructedWith($client, $response);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(NullDriver::class);
    }

    public function it_implements_interface()
    {
        $this->shouldImplement('Matthewbdaly\SMS\Contracts\Driver');
    }

    public function it_returns_the_driver_name()
    {
        $this->getDriver()->shouldReturn('Null');
    }

    public function it_returns_the_driver_endpoint()
    {
        $this->getEndpoint()->shouldReturn('');
    }

    public function it_sends_the_request()
    {
        $msg = [
            'to'      => '+44 01234 567890',
            'content' => 'Just testing',
        ];
        $this->sendRequest($msg)->shouldReturn(true);
    }
}
