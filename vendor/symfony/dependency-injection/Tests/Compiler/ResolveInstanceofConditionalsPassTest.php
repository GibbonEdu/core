<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Tests\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\BoundArgument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\ResolveChildDefinitionsPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveInstanceofConditionalsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ResolveInstanceofConditionalsPassTest extends TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $def = $container->register('foo', self::class)->addTag('tag')->setAutowired(true)->setChanges(array());
        $def->setInstanceofConditionals(array(
            parent::class => (new ChildDefinition(''))->setProperty('foo', 'bar')->addTag('baz', array('attr' => 123)),
        ));

        (new ResolveInstanceofConditionalsPass())->process($container);

        $parent = '.instanceof.'.parent::class.'.0.foo';
        $def = $container->getDefinition('foo');
        $this->assertEmpty($def->getInstanceofConditionals());
        $this->assertInstanceOf(ChildDefinition::class, $def);
        $this->assertTrue($def->isAutowired());
        $this->assertSame($parent, $def->getParent());
        $this->assertSame(array('tag' => array(array()), 'baz' => array(array('attr' => 123))), $def->getTags());

        $parent = $container->getDefinition($parent);
        $this->assertSame(array('foo' => 'bar'), $parent->getProperties());
        $this->assertSame(array(), $parent->getTags());
    }

    public function testProcessInheritance()
    {
        $container = new ContainerBuilder();

        $def = $container
            ->register('parent', parent::class)
            ->addMethodCall('foo', array('foo'));
        $def->setInstanceofConditionals(array(
            parent::class => (new ChildDefinition(''))->addMethodCall('foo', array('bar')),
        ));

        $def = (new ChildDefinition('parent'))->setClass(self::class);
        $container->setDefinition('child', $def);

        (new ResolveInstanceofConditionalsPass())->process($container);
        (new ResolveChildDefinitionsPass())->process($container);

        $expected = array(
            array('foo', array('bar')),
            array('foo', array('foo')),
        );

        $this->assertSame($expected, $container->getDefinition('parent')->getMethodCalls());
        $this->assertSame($expected, $container->getDefinition('child')->getMethodCalls());
    }

    public function testProcessDoesReplaceShared()
    {
        $container = new ContainerBuilder();

        $def = $container->register('foo', 'stdClass');
        $def->setInstanceofConditionals(array(
            'stdClass' => (new ChildDefinition(''))->setShared(false),
        ));

        (new ResolveInstanceofConditionalsPass())->process($container);

        $def = $container->getDefinition('foo');
        $this->assertFalse($def->isShared());
    }

    public function testProcessHandlesMultipleInheritance()
    {
        $container = new ContainerBuilder();

        $def = $container->register('foo', self::class)->setShared(true);

        $def->setInstanceofConditionals(array(
            parent::class => (new ChildDefinition(''))->setLazy(true)->setShared(false),
            self::class => (new ChildDefinition(''))->setAutowired(true),
        ));

        (new ResolveInstanceofConditionalsPass())->process($container);
        (new ResolveChildDefinitionsPass())->process($container);

        $def = $container->getDefinition('foo');
        $this->assertTrue($def->isAutowired());
        $this->assertTrue($def->isLazy());
        $this->assertTrue($def->isShared());
    }

    public function testProcessUsesAutoconfiguredInstanceof()
    {
        $container = new ContainerBuilder();
        $def = $container->register('normal_service', self::class);
        $def->setInstanceofConditionals(array(
            parent::class => (new ChildDefinition(''))
                ->addTag('local_instanceof_tag')
                ->setFactory('locally_set_factory'),
        ));
        $def->setAutoconfigured(true);
        $container->registerForAutoconfiguration(parent::class)
            ->addTag('autoconfigured_tag')
            ->setAutowired(true)
            ->setFactory('autoconfigured_factory');

        (new ResolveInstanceofConditionalsPass())->process($container);
        (new ResolveChildDefinitionsPass())->process($container);

        $def = $container->getDefinition('normal_service');
        // autowired thanks to the autoconfigured instanceof
        $this->assertTrue($def->isAutowired());
        // factory from the specific instanceof overrides global one
        $this->assertEquals('locally_set_factory', $def->getFactory());
        // tags are merged, the locally set one is first
        $this->assertSame(array('local_instanceof_tag' => array(array()), 'autoconfigured_tag' => array(array())), $def->getTags());
    }

    public function testAutoconfigureInstanceofDoesNotDuplicateTags()
    {
        $container = new ContainerBuilder();
        $def = $container->register('normal_service', self::class);
        $def
            ->addTag('duplicated_tag')
            ->addTag('duplicated_tag', array('and_attributes' => 1))
        ;
        $def->setInstanceofConditionals(array(
            parent::class => (new ChildDefinition(''))->addTag('duplicated_tag'),
        ));
        $def->setAutoconfigured(true);
        $container->registerForAutoconfiguration(parent::class)
            ->addTag('duplicated_tag', array('and_attributes' => 1))
        ;

        (new ResolveInstanceofConditionalsPass())->process($container);
        (new ResolveChildDefinitionsPass())->process($container);

        $def = $container->getDefinition('normal_service');
        $this->assertSame(array('duplicated_tag' => array(array(), array('and_attributes' => 1))), $def->getTags());
    }

    public function testProcessDoesNotUseAutoconfiguredInstanceofIfNotEnabled()
    {
        $container = new ContainerBuilder();
        $def = $container->register('normal_service', self::class);
        $def->setInstanceofConditionals(array(
            parent::class => (new ChildDefinition(''))
                ->addTag('foo_tag'),
        ));
        $container->registerForAutoconfiguration(parent::class)
            ->setAutowired(true);

        (new ResolveInstanceofConditionalsPass())->process($container);
        (new ResolveChildDefinitionsPass())->process($container);

        $def = $container->getDefinition('normal_service');
        $this->assertFalse($def->isAutowired());
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\RuntimeException
     * @expectedExceptionMessage "App\FakeInterface" is set as an "instanceof" conditional, but it does not exist.
     */
    public function testBadInterfaceThrowsException()
    {
        $container = new ContainerBuilder();
        $def = $container->register('normal_service', self::class);
        $def->setInstanceofConditionals(array(
            'App\\FakeInterface' => (new ChildDefinition(''))
                ->addTag('foo_tag'),
        ));

        (new ResolveInstanceofConditionalsPass())->process($container);
    }

    public function testBadInterfaceForAutomaticInstanceofIsOk()
    {
        $container = new ContainerBuilder();
        $container->register('normal_service', self::class)
            ->setAutoconfigured(true);
        $container->registerForAutoconfiguration('App\\FakeInterface')
            ->setAutowired(true);

        (new ResolveInstanceofConditionalsPass())->process($container);
        $this->assertTrue($container->hasDefinition('normal_service'));
    }

    /**
     * Test that autoconfigured calls are handled gracefully.
     */
    public function testProcessForAutoconfiguredCalls()
    {
        $container = new ContainerBuilder();

        $expected = array(
            array('setFoo', array(
                'plain_value',
                '%some_parameter%',
            )),
            array('callBar', array()),
            array('isBaz', array()),
        );

        $container->registerForAutoconfiguration(parent::class)->addMethodCall('setFoo', $expected[0][1]);
        $container->registerForAutoconfiguration(self::class)->addMethodCall('callBar');

        $def = $container->register('foo', self::class)->setAutoconfigured(true)->addMethodCall('isBaz');
        $this->assertEquals(
            array(array('isBaz', array())),
            $def->getMethodCalls(),
            'Definition shouldn\'t have only one method call.'
        );

        (new ResolveInstanceofConditionalsPass())->process($container);

        $this->assertEquals($expected, $container->findDefinition('foo')->getMethodCalls());
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @expectedExceptionMessage Autoconfigured instanceof for type "PHPUnit\Framework\TestCase" defines arguments but these are not supported and should be removed.
     */
    public function testProcessThrowsExceptionForArguments()
    {
        $container = new ContainerBuilder();
        $container->registerForAutoconfiguration(parent::class)
            ->addArgument('bar');

        (new ResolveInstanceofConditionalsPass())->process($container);
    }

    public function testMergeReset()
    {
        $container = new ContainerBuilder();

        $container
            ->register('bar', self::class)
            ->addArgument('a')
            ->addMethodCall('setB')
            ->setDecoratedService('foo')
            ->addTag('t')
            ->setInstanceofConditionals(array(
                parent::class => (new ChildDefinition(''))->addTag('bar'),
            ))
        ;

        (new ResolveInstanceofConditionalsPass())->process($container);

        $abstract = $container->getDefinition('.abstract.instanceof.bar');

        $this->assertEmpty($abstract->getArguments());
        $this->assertEmpty($abstract->getMethodCalls());
        $this->assertNull($abstract->getDecoratedService());
        $this->assertEmpty($abstract->getTags());
        $this->assertTrue($abstract->isAbstract());
    }

    public function testBindings()
    {
        $container = new ContainerBuilder();
        $def = $container->register('foo', self::class)->setBindings(array('$toto' => 123));
        $def->setInstanceofConditionals(array(parent::class => new ChildDefinition('')));

        (new ResolveInstanceofConditionalsPass())->process($container);

        $bindings = $container->getDefinition('foo')->getBindings();
        $this->assertSame(array('$toto'), array_keys($bindings));
        $this->assertInstanceOf(BoundArgument::class, $bindings['$toto']);
        $this->assertSame(123, $bindings['$toto']->getValues()[0]);
    }
}
