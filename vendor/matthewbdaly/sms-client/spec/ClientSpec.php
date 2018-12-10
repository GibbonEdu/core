<?php

namespace spec\Matthewbdaly\SMS;

use Matthewbdaly\SMS\Client;
use Matthewbdaly\SMS\Contracts\Driver;
use PhpSpec\ObjectBehavior;

class ClientSpec extends ObjectBehavior
{
    public function let(Driver $driver)
    {
        $this->beConstructedWith($driver);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Client::class);
    }

    public function it_implements_interface()
    {
        $this->shouldImplement('Matthewbdaly\SMS\Contracts\Client');
    }

    public function it_returns_the_driver_name(Driver $driver)
    {
        $driver->getDriver()->willReturn('Test');
        $this->getDriver()->shouldReturn('Test');
    }

    public function it_sends_a_message(Driver $driver)
    {
        $msg = [
            'to'      => '+44 01234 567890',
            'content' => 'Just testing',
        ];
        $driver->sendRequest($msg)->willReturn(true);
        $this->send($msg)->shouldReturn(true);
    }
}
