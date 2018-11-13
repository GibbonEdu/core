<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Command\TranslationDebugCommand;
use Symfony\Component\Filesystem\Filesystem;

class TranslationDebugCommandTest extends TestCase
{
    private $fs;
    private $translationDir;

    public function testDebugMissingMessages()
    {
        $tester = $this->createCommandTester($this->getContainer(array('foo' => 'foo')));
        $tester->execute(array('locale' => 'en', 'bundle' => 'foo'));

        $this->assertRegExp('/missing/', $tester->getDisplay());
    }

    public function testDebugUnusedMessages()
    {
        $tester = $this->createCommandTester($this->getContainer(array(), array('foo' => 'foo')));
        $tester->execute(array('locale' => 'en', 'bundle' => 'foo'));

        $this->assertRegExp('/unused/', $tester->getDisplay());
    }

    public function testDebugFallbackMessages()
    {
        $tester = $this->createCommandTester($this->getContainer(array(), array('foo' => 'foo')));
        $tester->execute(array('locale' => 'fr', 'bundle' => 'foo'));

        $this->assertRegExp('/fallback/', $tester->getDisplay());
    }

    public function testNoDefinedMessages()
    {
        $tester = $this->createCommandTester($this->getContainer());
        $tester->execute(array('locale' => 'fr', 'bundle' => 'test'));

        $this->assertRegExp('/No defined or extracted messages for locale "fr"/', $tester->getDisplay());
    }

    public function testDebugDefaultDirectory()
    {
        $tester = $this->createCommandTester($this->getContainer(array('foo' => 'foo'), array('bar' => 'bar')));
        $tester->execute(array('locale' => 'en'));

        $this->assertRegExp('/missing/', $tester->getDisplay());
        $this->assertRegExp('/unused/', $tester->getDisplay());
    }

    public function testDebugCustomDirectory()
    {
        $kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\KernelInterface')->getMock();
        $kernel->expects($this->once())
            ->method('getBundle')
            ->with($this->equalTo($this->translationDir))
            ->willThrowException(new \InvalidArgumentException());

        $tester = $this->createCommandTester($this->getContainer(array('foo' => 'foo'), array('bar' => 'bar'), $kernel));
        $tester->execute(array('locale' => 'en', 'bundle' => $this->translationDir));

        $this->assertRegExp('/missing/', $tester->getDisplay());
        $this->assertRegExp('/unused/', $tester->getDisplay());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDebugInvalidDirectory()
    {
        $kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\KernelInterface')->getMock();
        $kernel->expects($this->once())
            ->method('getBundle')
            ->with($this->equalTo('dir'))
            ->will($this->throwException(new \InvalidArgumentException()));

        $tester = $this->createCommandTester($this->getContainer(array(), array(), $kernel));
        $tester->execute(array('locale' => 'en', 'bundle' => 'dir'));
    }

    protected function setUp()
    {
        $this->fs = new Filesystem();
        $this->translationDir = sys_get_temp_dir().'/'.uniqid('sf2_translation', true);
        $this->fs->mkdir($this->translationDir.'/Resources/translations');
        $this->fs->mkdir($this->translationDir.'/Resources/views');
    }

    protected function tearDown()
    {
        $this->fs->remove($this->translationDir);
    }

    /**
     * @return CommandTester
     */
    private function createCommandTester($container)
    {
        $command = new TranslationDebugCommand();
        $command->setContainer($container);

        $application = new Application();
        $application->add($command);

        return new CommandTester($application->find('debug:translation'));
    }

    private function getContainer($extractedMessages = array(), $loadedMessages = array(), $kernel = null)
    {
        $translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $translator
            ->expects($this->any())
            ->method('getFallbackLocales')
            ->will($this->returnValue(array('en')));

        $extractor = $this->getMockBuilder('Symfony\Component\Translation\Extractor\ExtractorInterface')->getMock();
        $extractor
            ->expects($this->any())
            ->method('extract')
            ->will(
                $this->returnCallback(function ($path, $catalogue) use ($extractedMessages) {
                    $catalogue->add($extractedMessages);
                })
            );

        $loader = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader')->getMock();
        $loader
            ->expects($this->any())
            ->method('loadMessages')
            ->will(
                $this->returnCallback(function ($path, $catalogue) use ($loadedMessages) {
                    $catalogue->add($loadedMessages);
                })
            );

        if (null === $kernel) {
            $kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\KernelInterface')->getMock();
            $kernel
                ->expects($this->any())
                ->method('getBundle')
                ->will($this->returnValueMap(array(
                    array('foo', true, $this->getBundle($this->translationDir)),
                    array('test', true, $this->getBundle('test')),
                )));
        }

        $kernel
            ->expects($this->any())
            ->method('getRootDir')
            ->will($this->returnValue($this->translationDir));

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')->getMock();
        $container
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap(array(
                array('translation.extractor', 1, $extractor),
                array('translation.loader', 1, $loader),
                array('translator', 1, $translator),
                array('kernel', 1, $kernel),
            )));

        return $container;
    }

    private function getBundle($path)
    {
        $bundle = $this->getMockBuilder('Symfony\Component\HttpKernel\Bundle\BundleInterface')->getMock();
        $bundle
            ->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path))
        ;

        return $bundle;
    }
}
