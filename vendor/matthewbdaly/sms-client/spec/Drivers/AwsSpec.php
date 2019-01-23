<?php

namespace spec\Matthewbdaly\SMS\Drivers;

use Matthewbdaly\SMS\Drivers\Aws;
use PhpSpec\ObjectBehavior;
use Aws\Sns\SnsClient;

class AwsSpec extends ObjectBehavior
{
    public function let(SnsClient $sns)
    {
        $this->beConstructedWith([], $sns);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Aws::class);
    }

    public function it_implements_interface()
    {
        $this->shouldImplement('Matthewbdaly\SMS\Contracts\Driver');
    }

    public function it_throws_exception_if_misconfigured()
    {
        $config = [
        ];
        $this->beConstructedWith($config);
        $this->shouldThrow('Matthewbdaly\SMS\Exceptions\DriverNotConfiguredException')->during('__construct', [$config]);
    }

    public function it_returns_the_driver_name()
    {
        $this->getDriver()->shouldReturn('Aws');
    }

    public function it_returns_the_driver_endpoint()
    {
        $this->getEndpoint()->shouldReturn('');
    }

    public function it_can_be_constructed_with_config_only()
    {
        $config = [
            'api_key'    => 'foo',
            'api_secret' => 'bar',
            'api_region' => 'ap-southeast-2'
        ];
        $this->beConstructedWith($config);
        $this->getDriver()->shouldReturn('Aws');
    }

    public function it_sends_the_request(SnsClient $sns)
    {
        $msg = [
            'to'      => '+44 01234 567890',
            'from'    => 'Tester',
            'content' => 'Just testing',
        ];
        $args = [
            "SenderID" => $msg['from'],
            "SMSType" => "Transactional",
            "Message" => $msg['content'],
            "PhoneNumber" => $msg['to']
        ];

        $sns->publish($args)->shouldBeCalled();
        $this->beConstructedWith([], $sns);
        $this->sendRequest($msg)->shouldReturn(true);
    }
}
