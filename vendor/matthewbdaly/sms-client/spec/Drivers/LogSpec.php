<?php

namespace spec\Matthewbdaly\SMS\Drivers;

use Matthewbdaly\SMS\Drivers\Log;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

class LogSpec extends ObjectBehavior
{
    public function let(LoggerInterface $logger)
    {
        $this->beConstructedWith($logger);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Log::class);
    }

    public function it_implements_interface()
    {
        $this->shouldImplement('Matthewbdaly\SMS\Contracts\Driver');
    }

    public function it_returns_the_driver_name()
    {
        $this->getDriver()->shouldReturn('Log');
    }

    public function it_returns_the_driver_endpoint()
    {
        $this->getEndpoint()->shouldReturn('');
    }

    public function it_sends_the_request(LoggerInterface $logger)
    {
        $msg = [
            'to'      => '+44 01234 567890',
            'content' => 'Just testing',
        ];
        $this->beConstructedWith($logger);
        $this->sendRequest($msg)->shouldReturn(true);
        $logger->info('Message sent', $msg)->shouldHaveBeenCalled();
    }
}
