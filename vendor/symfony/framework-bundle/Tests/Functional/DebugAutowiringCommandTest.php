<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @group functional
 */
class DebugAutowiringCommandTest extends WebTestCase
{
    public function testBasicFunctionality()
    {
        static::bootKernel(array('test_case' => 'ContainerDebug', 'root_config' => 'config.yml'));

        $application = new Application(static::$kernel);
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);
        $tester->run(array('command' => 'debug:autowiring'));

        $this->assertContains('Symfony\Component\HttpKernel\HttpKernelInterface', $tester->getDisplay());
        $this->assertContains('alias to http_kernel', $tester->getDisplay());
    }

    public function testSearchArgument()
    {
        static::bootKernel(array('test_case' => 'ContainerDebug', 'root_config' => 'config.yml'));

        $application = new Application(static::$kernel);
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);
        $tester->run(array('command' => 'debug:autowiring', 'search' => 'kern'));

        $this->assertContains('Symfony\Component\HttpKernel\HttpKernelInterface', $tester->getDisplay());
        $this->assertNotContains('Symfony\Component\Routing\RouterInterface', $tester->getDisplay());
    }

    public function testSearchNoResults()
    {
        static::bootKernel(array('test_case' => 'ContainerDebug', 'root_config' => 'config.yml'));

        $application = new Application(static::$kernel);
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);
        $tester->run(array('command' => 'debug:autowiring', 'search' => 'foo_fake'), array('capture_stderr_separately' => true));

        $this->assertContains('No autowirable classes or interfaces found matching "foo_fake"', $tester->getErrorOutput());
        $this->assertEquals(1, $tester->getStatusCode());
    }
}
