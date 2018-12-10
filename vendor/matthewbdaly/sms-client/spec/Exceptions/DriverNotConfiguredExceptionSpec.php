<?php

namespace spec\Matthewbdaly\SMS\Exceptions;

use Matthewbdaly\SMS\Exceptions\DriverNotConfiguredException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DriverNotConfiguredExceptionSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(DriverNotConfiguredException::class);
    }

    function it_is_an_exception()
    {
        $this->shouldHaveType(\Exception::class);
    }
}
