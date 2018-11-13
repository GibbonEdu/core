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
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group functional
 */
class RouterDebugCommandTest extends WebTestCase
{
    private $application;

    protected function setUp()
    {
        $kernel = static::createKernel(array('test_case' => 'RouterDebug', 'root_config' => 'config.yml'));
        $this->application = new Application($kernel);
    }

    public function testDumpAllRoutes()
    {
        $tester = $this->createCommandTester();
        $ret = $tester->execute(array());
        $display = $tester->getDisplay();

        $this->assertSame(0, $ret, 'Returns 0 in case of success');
        $this->assertContains('routerdebug_test', $display);
        $this->assertContains('/test', $display);
        $this->assertContains('/session', $display);
    }

    public function testDumpOneRoute()
    {
        $tester = $this->createCommandTester();
        $ret = $tester->execute(array('name' => 'routerdebug_session_welcome'));

        $this->assertSame(0, $ret, 'Returns 0 in case of success');
        $this->assertContains('routerdebug_session_welcome', $tester->getDisplay());
        $this->assertContains('/session', $tester->getDisplay());
    }

    public function testSearchMultipleRoutes()
    {
        $tester = $this->createCommandTester();
        $tester->setInputs(array(3));
        $ret = $tester->execute(array('name' => 'routerdebug'), array('interactive' => true));

        $this->assertSame(0, $ret, 'Returns 0 in case of success');
        $this->assertContains('Select one of the matching routes:', $tester->getDisplay());
        $this->assertContains('routerdebug_test', $tester->getDisplay());
        $this->assertContains('/test', $tester->getDisplay());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The route "gerard" does not exist.
     */
    public function testSearchWithThrow()
    {
        $tester = $this->createCommandTester();
        $tester->execute(array('name' => 'gerard'), array('interactive' => true));
    }

    private function createCommandTester(): CommandTester
    {
        $command = $this->application->get('debug:router');

        return new CommandTester($command);
    }
}
