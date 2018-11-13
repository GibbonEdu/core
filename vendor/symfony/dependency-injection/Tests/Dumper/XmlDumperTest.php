<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Tests\Dumper;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\XmlDumper;

class XmlDumperTest extends TestCase
{
    protected static $fixturesPath;

    public static function setUpBeforeClass()
    {
        self::$fixturesPath = realpath(__DIR__.'/../Fixtures/');
    }

    public function testDump()
    {
        $dumper = new XmlDumper(new ContainerBuilder());

        $this->assertXmlStringEqualsXmlFile(self::$fixturesPath.'/xml/services1.xml', $dumper->dump(), '->dump() dumps an empty container as an empty XML file');
    }

    public function testExportParameters()
    {
        $container = include self::$fixturesPath.'//containers/container8.php';
        $dumper = new XmlDumper($container);
        $this->assertXmlStringEqualsXmlFile(self::$fixturesPath.'/xml/services8.xml', $dumper->dump(), '->dump() dumps parameters');
    }

    public function testAddParameters()
    {
        $container = include self::$fixturesPath.'//containers/container8.php';
        $dumper = new XmlDumper($container);
        $this->assertXmlStringEqualsXmlFile(self::$fixturesPath.'/xml/services8.xml', $dumper->dump(), '->dump() dumps parameters');
    }

    public function testAddService()
    {
        $container = include self::$fixturesPath.'/containers/container9.php';
        $dumper = new XmlDumper($container);

        $this->assertEquals(str_replace('%path%', self::$fixturesPath.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR, file_get_contents(self::$fixturesPath.'/xml/services9.xml')), $dumper->dump(), '->dump() dumps services');

        $dumper = new XmlDumper($container = new ContainerBuilder());
        $container->register('foo', 'FooClass')->addArgument(new \stdClass());
        try {
            $dumper->dump();
            $this->fail('->dump() throws a RuntimeException if the container to be dumped has reference to objects or resources');
        } catch (\Exception $e) {
            $this->assertInstanceOf('\RuntimeException', $e, '->dump() throws a RuntimeException if the container to be dumped has reference to objects or resources');
            $this->assertEquals('Unable to dump a service container if a parameter is an object or a resource.', $e->getMessage(), '->dump() throws a RuntimeException if the container to be dumped has reference to objects or resources');
        }
    }

    public function testDumpAnonymousServices()
    {
        $container = include self::$fixturesPath.'/containers/container11.php';
        $dumper = new XmlDumper($container);
        $this->assertEquals('<?xml version="1.0" encoding="utf-8"?>
<container xmlns="http://symfony.com/schema/dic/services" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">
  <services>
    <service id="service_container" class="Symfony\Component\DependencyInjection\ContainerInterface" synthetic="true"/>
    <service id="foo" class="FooClass">
      <argument type="service">
        <service class="BarClass">
          <argument type="service">
            <service class="BazClass"/>
          </argument>
        </service>
      </argument>
    </service>
    <service id="Psr\Container\ContainerInterface" alias="service_container" public="false"/>
    <service id="Symfony\Component\DependencyInjection\ContainerInterface" alias="service_container" public="false"/>
  </services>
</container>
', $dumper->dump());
    }

    public function testDumpEntities()
    {
        $container = include self::$fixturesPath.'/containers/container12.php';
        $dumper = new XmlDumper($container);
        $this->assertEquals("<?xml version=\"1.0\" encoding=\"utf-8\"?>
<container xmlns=\"http://symfony.com/schema/dic/services\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd\">
  <services>
    <service id=\"service_container\" class=\"Symfony\Component\DependencyInjection\ContainerInterface\" synthetic=\"true\"/>
    <service id=\"foo\" class=\"FooClass\Foo\">
      <tag name=\"foo&quot;bar\bar\" foo=\"foo&quot;barřž€\"/>
      <argument>foo&lt;&gt;&amp;bar</argument>
    </service>
    <service id=\"Psr\Container\ContainerInterface\" alias=\"service_container\" public=\"false\"/>
    <service id=\"Symfony\Component\DependencyInjection\ContainerInterface\" alias=\"service_container\" public=\"false\"/>
  </services>
</container>
", $dumper->dump());
    }

    /**
     * @dataProvider provideDecoratedServicesData
     */
    public function testDumpDecoratedServices($expectedXmlDump, $container)
    {
        $dumper = new XmlDumper($container);
        $this->assertEquals($expectedXmlDump, $dumper->dump());
    }

    public function provideDecoratedServicesData()
    {
        $fixturesPath = realpath(__DIR__.'/../Fixtures/');

        return array(
            array("<?xml version=\"1.0\" encoding=\"utf-8\"?>
<container xmlns=\"http://symfony.com/schema/dic/services\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd\">
  <services>
    <service id=\"service_container\" class=\"Symfony\Component\DependencyInjection\ContainerInterface\" synthetic=\"true\"/>
    <service id=\"foo\" class=\"FooClass\Foo\" decorates=\"bar\" decoration-inner-name=\"bar.woozy\"/>
    <service id=\"Psr\Container\ContainerInterface\" alias=\"service_container\" public=\"false\"/>
    <service id=\"Symfony\Component\DependencyInjection\ContainerInterface\" alias=\"service_container\" public=\"false\"/>
  </services>
</container>
", include $fixturesPath.'/containers/container15.php'),
            array("<?xml version=\"1.0\" encoding=\"utf-8\"?>
<container xmlns=\"http://symfony.com/schema/dic/services\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd\">
  <services>
    <service id=\"service_container\" class=\"Symfony\Component\DependencyInjection\ContainerInterface\" synthetic=\"true\"/>
    <service id=\"foo\" class=\"FooClass\Foo\" decorates=\"bar\"/>
    <service id=\"Psr\Container\ContainerInterface\" alias=\"service_container\" public=\"false\"/>
    <service id=\"Symfony\Component\DependencyInjection\ContainerInterface\" alias=\"service_container\" public=\"false\"/>
  </services>
</container>
", include $fixturesPath.'/containers/container16.php'),
        );
    }

    /**
     * @dataProvider provideCompiledContainerData
     */
    public function testCompiledContainerCanBeDumped($containerFile)
    {
        $fixturesPath = __DIR__.'/../Fixtures';
        $container = require $fixturesPath.'/containers/'.$containerFile.'.php';
        $container->compile();
        $dumper = new XmlDumper($container);
        $dumper->dump();

        $this->addToAssertionCount(1);
    }

    public function provideCompiledContainerData()
    {
        return array(
            array('container8'),
            array('container9'),
            array('container11'),
            array('container12'),
            array('container14'),
        );
    }

    public function testDumpInlinedServices()
    {
        $container = include self::$fixturesPath.'/containers/container21.php';
        $dumper = new XmlDumper($container);

        $this->assertEquals(file_get_contents(self::$fixturesPath.'/xml/services21.xml'), $dumper->dump());
    }

    public function testDumpAutowireData()
    {
        $container = include self::$fixturesPath.'/containers/container24.php';
        $dumper = new XmlDumper($container);

        $this->assertEquals(file_get_contents(self::$fixturesPath.'/xml/services24.xml'), $dumper->dump());
    }

    public function testDumpAbstractServices()
    {
        $container = include self::$fixturesPath.'/containers/container_abstract.php';
        $dumper = new XmlDumper($container);

        $this->assertEquals(file_get_contents(self::$fixturesPath.'/xml/services_abstract.xml'), $dumper->dump());
    }
}
