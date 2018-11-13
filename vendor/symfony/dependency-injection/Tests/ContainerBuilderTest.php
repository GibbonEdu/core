<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Tests;

require_once __DIR__.'/Fixtures/includes/classes.php';
require_once __DIR__.'/Fixtures/includes/ProjectExtension.php';

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Component\Config\Resource\ComposerResource;
use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\TypedReference;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\DependencyInjection\Tests\Fixtures\CustomDefinition;
use Symfony\Component\DependencyInjection\Tests\Fixtures\CaseSensitiveClass;
use Symfony\Component\ExpressionLanguage\Expression;

class ContainerBuilderTest extends TestCase
{
    public function testDefaultRegisteredDefinitions()
    {
        $builder = new ContainerBuilder();

        $this->assertCount(1, $builder->getDefinitions());
        $this->assertTrue($builder->hasDefinition('service_container'));

        $definition = $builder->getDefinition('service_container');
        $this->assertInstanceOf(Definition::class, $definition);
        $this->assertTrue($definition->isSynthetic());
        $this->assertSame(ContainerInterface::class, $definition->getClass());
        $this->assertTrue($builder->hasAlias(PsrContainerInterface::class));
        $this->assertTrue($builder->hasAlias(ContainerInterface::class));
    }

    public function testDefinitions()
    {
        $builder = new ContainerBuilder();
        $definitions = array(
            'foo' => new Definition('Bar\FooClass'),
            'bar' => new Definition('BarClass'),
        );
        $builder->setDefinitions($definitions);
        $this->assertEquals($definitions, $builder->getDefinitions(), '->setDefinitions() sets the service definitions');
        $this->assertTrue($builder->hasDefinition('foo'), '->hasDefinition() returns true if a service definition exists');
        $this->assertFalse($builder->hasDefinition('foobar'), '->hasDefinition() returns false if a service definition does not exist');

        $builder->setDefinition('foobar', $foo = new Definition('FooBarClass'));
        $this->assertEquals($foo, $builder->getDefinition('foobar'), '->getDefinition() returns a service definition if defined');
        $this->assertTrue($builder->setDefinition('foobar', $foo = new Definition('FooBarClass')) === $foo, '->setDefinition() implements a fluid interface by returning the service reference');

        $builder->addDefinitions($defs = array('foobar' => new Definition('FooBarClass')));
        $this->assertEquals(array_merge($definitions, $defs), $builder->getDefinitions(), '->addDefinitions() adds the service definitions');

        try {
            $builder->getDefinition('baz');
            $this->fail('->getDefinition() throws a ServiceNotFoundException if the service definition does not exist');
        } catch (ServiceNotFoundException $e) {
            $this->assertEquals('You have requested a non-existent service "baz".', $e->getMessage(), '->getDefinition() throws a ServiceNotFoundException if the service definition does not exist');
        }
    }

    /**
     * @group legacy
     * @expectedDeprecation The "deprecated_foo" service is deprecated. You should stop using it, as it will soon be removed.
     */
    public function testCreateDeprecatedService()
    {
        $definition = new Definition('stdClass');
        $definition->setDeprecated(true);

        $builder = new ContainerBuilder();
        $builder->setDefinition('deprecated_foo', $definition);
        $builder->get('deprecated_foo');
    }

    public function testRegister()
    {
        $builder = new ContainerBuilder();
        $builder->register('foo', 'Bar\FooClass');
        $this->assertTrue($builder->hasDefinition('foo'), '->register() registers a new service definition');
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Definition', $builder->getDefinition('foo'), '->register() returns the newly created Definition instance');
    }

    public function testAutowire()
    {
        $builder = new ContainerBuilder();
        $builder->autowire('foo', 'Bar\FooClass');

        $this->assertTrue($builder->hasDefinition('foo'), '->autowire() registers a new service definition');
        $this->assertTrue($builder->getDefinition('foo')->isAutowired(), '->autowire() creates autowired definitions');
    }

    public function testHas()
    {
        $builder = new ContainerBuilder();
        $this->assertFalse($builder->has('foo'), '->has() returns false if the service does not exist');
        $builder->register('foo', 'Bar\FooClass');
        $this->assertTrue($builder->has('foo'), '->has() returns true if a service definition exists');
        $builder->set('bar', new \stdClass());
        $this->assertTrue($builder->has('bar'), '->has() returns true if a service exists');
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @expectedExceptionMessage You have requested a non-existent service "foo".
     */
    public function testGetThrowsExceptionIfServiceDoesNotExist()
    {
        $builder = new ContainerBuilder();
        $builder->get('foo');
    }

    public function testGetReturnsNullIfServiceDoesNotExistAndInvalidReferenceIsUsed()
    {
        $builder = new ContainerBuilder();

        $this->assertNull($builder->get('foo', ContainerInterface::NULL_ON_INVALID_REFERENCE), '->get() returns null if the service does not exist and NULL_ON_INVALID_REFERENCE is passed as a second argument');
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function testGetThrowsCircularReferenceExceptionIfServiceHasReferenceToItself()
    {
        $builder = new ContainerBuilder();
        $builder->register('baz', 'stdClass')->setArguments(array(new Reference('baz')));
        $builder->get('baz');
    }

    public function testGetReturnsSameInstanceWhenServiceIsShared()
    {
        $builder = new ContainerBuilder();
        $builder->register('bar', 'stdClass');

        $this->assertTrue($builder->get('bar') === $builder->get('bar'), '->get() always returns the same instance if the service is shared');
    }

    public function testGetCreatesServiceBasedOnDefinition()
    {
        $builder = new ContainerBuilder();
        $builder->register('foo', 'stdClass');

        $this->assertInternalType('object', $builder->get('foo'), '->get() returns the service definition associated with the id');
    }

    public function testGetReturnsRegisteredService()
    {
        $builder = new ContainerBuilder();
        $builder->set('bar', $bar = new \stdClass());

        $this->assertSame($bar, $builder->get('bar'), '->get() returns the service associated with the id');
    }

    public function testRegisterDoesNotOverrideExistingService()
    {
        $builder = new ContainerBuilder();
        $builder->set('bar', $bar = new \stdClass());
        $builder->register('bar', 'stdClass');

        $this->assertSame($bar, $builder->get('bar'), '->get() returns the service associated with the id even if a definition has been defined');
    }

    public function testNonSharedServicesReturnsDifferentInstances()
    {
        $builder = new ContainerBuilder();
        $builder->register('bar', 'stdClass')->setShared(false);

        $this->assertNotSame($builder->get('bar'), $builder->get('bar'));
    }

    /**
     * @expectedException        \Symfony\Component\DependencyInjection\Exception\RuntimeException
     * @expectedExceptionMessage You have requested a synthetic service ("foo"). The DIC does not know how to construct this service.
     */
    public function testGetUnsetLoadingServiceWhenCreateServiceThrowsAnException()
    {
        $builder = new ContainerBuilder();
        $builder->register('foo', 'stdClass')->setSynthetic(true);

        // we expect a RuntimeException here as foo is synthetic
        try {
            $builder->get('foo');
        } catch (RuntimeException $e) {
        }

        // we must also have the same RuntimeException here
        $builder->get('foo');
    }

    public function testGetServiceIds()
    {
        $builder = new ContainerBuilder();
        $builder->register('foo', 'stdClass');
        $builder->bar = $bar = new \stdClass();
        $builder->register('bar', 'stdClass');
        $this->assertEquals(
            array(
                'service_container',
                'foo',
                'bar',
                'Psr\Container\ContainerInterface',
                'Symfony\Component\DependencyInjection\ContainerInterface',
            ),
            $builder->getServiceIds(),
            '->getServiceIds() returns all defined service ids'
        );
    }

    public function testAliases()
    {
        $builder = new ContainerBuilder();
        $builder->register('foo', 'stdClass');
        $builder->setAlias('bar', 'foo');
        $this->assertTrue($builder->hasAlias('bar'), '->hasAlias() returns true if the alias exists');
        $this->assertFalse($builder->hasAlias('foobar'), '->hasAlias() returns false if the alias does not exist');
        $this->assertEquals('foo', (string) $builder->getAlias('bar'), '->getAlias() returns the aliased service');
        $this->assertTrue($builder->has('bar'), '->setAlias() defines a new service');
        $this->assertTrue($builder->get('bar') === $builder->get('foo'), '->setAlias() creates a service that is an alias to another one');

        try {
            $builder->setAlias('foobar', 'foobar');
            $this->fail('->setAlias() throws an InvalidArgumentException if the alias references itself');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('An alias can not reference itself, got a circular reference on "foobar".', $e->getMessage(), '->setAlias() throws an InvalidArgumentException if the alias references itself');
        }

        try {
            $builder->getAlias('foobar');
            $this->fail('->getAlias() throws an InvalidArgumentException if the alias does not exist');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('The service alias "foobar" does not exist.', $e->getMessage(), '->getAlias() throws an InvalidArgumentException if the alias does not exist');
        }
    }

    public function testGetAliases()
    {
        $builder = new ContainerBuilder();
        $builder->setAlias('bar', 'foo');
        $builder->setAlias('foobar', 'foo');
        $builder->setAlias('moo', new Alias('foo', false));

        $aliases = $builder->getAliases();
        $this->assertEquals('foo', (string) $aliases['bar']);
        $this->assertTrue($aliases['bar']->isPublic());
        $this->assertEquals('foo', (string) $aliases['foobar']);
        $this->assertEquals('foo', (string) $aliases['moo']);
        $this->assertFalse($aliases['moo']->isPublic());

        $builder->register('bar', 'stdClass');
        $this->assertFalse($builder->hasAlias('bar'));

        $builder->set('foobar', 'stdClass');
        $builder->set('moo', 'stdClass');
        $this->assertCount(2, $builder->getAliases(), '->getAliases() does not return aliased services that have been overridden');
    }

    public function testSetAliases()
    {
        $builder = new ContainerBuilder();
        $builder->setAliases(array('bar' => 'foo', 'foobar' => 'foo'));

        $aliases = $builder->getAliases();
        $this->assertTrue(isset($aliases['bar']));
        $this->assertTrue(isset($aliases['foobar']));
    }

    public function testAddAliases()
    {
        $builder = new ContainerBuilder();
        $builder->setAliases(array('bar' => 'foo'));
        $builder->addAliases(array('foobar' => 'foo'));

        $aliases = $builder->getAliases();
        $this->assertTrue(isset($aliases['bar']));
        $this->assertTrue(isset($aliases['foobar']));
    }

    public function testSetReplacesAlias()
    {
        $builder = new ContainerBuilder();
        $builder->setAlias('alias', 'aliased');
        $builder->set('aliased', new \stdClass());

        $builder->set('alias', $foo = new \stdClass());
        $this->assertSame($foo, $builder->get('alias'), '->set() replaces an existing alias');
    }

    public function testAliasesKeepInvalidBehavior()
    {
        $builder = new ContainerBuilder();

        $aliased = new Definition('stdClass');
        $aliased->addMethodCall('setBar', array(new Reference('bar', ContainerInterface::IGNORE_ON_INVALID_REFERENCE)));
        $builder->setDefinition('aliased', $aliased);
        $builder->setAlias('alias', 'aliased');

        $this->assertEquals(new \stdClass(), $builder->get('alias'));
    }

    public function testAddGetCompilerPass()
    {
        $builder = new ContainerBuilder();
        $builder->setResourceTracking(false);
        $defaultPasses = $builder->getCompiler()->getPassConfig()->getPasses();
        $builder->addCompilerPass($pass1 = $this->getMockBuilder('Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface')->getMock(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -5);
        $builder->addCompilerPass($pass2 = $this->getMockBuilder('Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface')->getMock(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 10);

        $passes = $builder->getCompiler()->getPassConfig()->getPasses();
        $this->assertCount(count($passes) - 2, $defaultPasses);
        // Pass 1 is executed later
        $this->assertTrue(array_search($pass1, $passes, true) > array_search($pass2, $passes, true));
    }

    public function testCreateService()
    {
        $builder = new ContainerBuilder();
        $builder->register('foo1', 'Bar\FooClass')->setFile(__DIR__.'/Fixtures/includes/foo.php');
        $builder->register('foo2', 'Bar\FooClass')->setFile(__DIR__.'/Fixtures/includes/%file%.php');
        $builder->setParameter('file', 'foo');
        $this->assertInstanceOf('\Bar\FooClass', $builder->get('foo1'), '->createService() requires the file defined by the service definition');
        $this->assertInstanceOf('\Bar\FooClass', $builder->get('foo2'), '->createService() replaces parameters in the file provided by the service definition');
    }

    public function testCreateProxyWithRealServiceInstantiator()
    {
        $builder = new ContainerBuilder();

        $builder->register('foo1', 'Bar\FooClass')->setFile(__DIR__.'/Fixtures/includes/foo.php');
        $builder->getDefinition('foo1')->setLazy(true);

        $foo1 = $builder->get('foo1');

        $this->assertSame($foo1, $builder->get('foo1'), 'The same proxy is retrieved on multiple subsequent calls');
        $this->assertSame('Bar\FooClass', get_class($foo1));
    }

    public function testCreateServiceClass()
    {
        $builder = new ContainerBuilder();
        $builder->register('foo1', '%class%');
        $builder->setParameter('class', 'stdClass');
        $this->assertInstanceOf('\stdClass', $builder->get('foo1'), '->createService() replaces parameters in the class provided by the service definition');
    }

    public function testCreateServiceArguments()
    {
        $builder = new ContainerBuilder();
        $builder->register('bar', 'stdClass');
        $builder->register('foo1', 'Bar\FooClass')->addArgument(array('foo' => '%value%', '%value%' => 'foo', new Reference('bar'), '%%unescape_it%%'));
        $builder->setParameter('value', 'bar');
        $this->assertEquals(array('foo' => 'bar', 'bar' => 'foo', $builder->get('bar'), '%unescape_it%'), $builder->get('foo1')->arguments, '->createService() replaces parameters and service references in the arguments provided by the service definition');
    }

    public function testCreateServiceFactory()
    {
        $builder = new ContainerBuilder();
        $builder->register('foo', 'Bar\FooClass')->setFactory('Bar\FooClass::getInstance');
        $builder->register('qux', 'Bar\FooClass')->setFactory(array('Bar\FooClass', 'getInstance'));
        $builder->register('bar', 'Bar\FooClass')->setFactory(array(new Definition('Bar\FooClass'), 'getInstance'));
        $builder->register('baz', 'Bar\FooClass')->setFactory(array(new Reference('bar'), 'getInstance'));

        $this->assertTrue($builder->get('foo')->called, '->createService() calls the factory method to create the service instance');
        $this->assertTrue($builder->get('qux')->called, '->createService() calls the factory method to create the service instance');
        $this->assertTrue($builder->get('bar')->called, '->createService() uses anonymous service as factory');
        $this->assertTrue($builder->get('baz')->called, '->createService() uses another service as factory');
    }

    public function testCreateServiceMethodCalls()
    {
        $builder = new ContainerBuilder();
        $builder->register('bar', 'stdClass');
        $builder->register('foo1', 'Bar\FooClass')->addMethodCall('setBar', array(array('%value%', new Reference('bar'))));
        $builder->setParameter('value', 'bar');
        $this->assertEquals(array('bar', $builder->get('bar')), $builder->get('foo1')->bar, '->createService() replaces the values in the method calls arguments');
    }

    public function testCreateServiceMethodCallsWithEscapedParam()
    {
        $builder = new ContainerBuilder();
        $builder->register('bar', 'stdClass');
        $builder->register('foo1', 'Bar\FooClass')->addMethodCall('setBar', array(array('%%unescape_it%%')));
        $builder->setParameter('value', 'bar');
        $this->assertEquals(array('%unescape_it%'), $builder->get('foo1')->bar, '->createService() replaces the values in the method calls arguments');
    }

    public function testCreateServiceProperties()
    {
        $builder = new ContainerBuilder();
        $builder->register('bar', 'stdClass');
        $builder->register('foo1', 'Bar\FooClass')->setProperty('bar', array('%value%', new Reference('bar'), '%%unescape_it%%'));
        $builder->setParameter('value', 'bar');
        $this->assertEquals(array('bar', $builder->get('bar'), '%unescape_it%'), $builder->get('foo1')->bar, '->createService() replaces the values in the properties');
    }

    public function testCreateServiceConfigurator()
    {
        $builder = new ContainerBuilder();
        $builder->register('foo1', 'Bar\FooClass')->setConfigurator('sc_configure');
        $builder->register('foo2', 'Bar\FooClass')->setConfigurator(array('%class%', 'configureStatic'));
        $builder->setParameter('class', 'BazClass');
        $builder->register('baz', 'BazClass');
        $builder->register('foo3', 'Bar\FooClass')->setConfigurator(array(new Reference('baz'), 'configure'));
        $builder->register('foo4', 'Bar\FooClass')->setConfigurator(array($builder->getDefinition('baz'), 'configure'));
        $builder->register('foo5', 'Bar\FooClass')->setConfigurator('foo');

        $this->assertTrue($builder->get('foo1')->configured, '->createService() calls the configurator');
        $this->assertTrue($builder->get('foo2')->configured, '->createService() calls the configurator');
        $this->assertTrue($builder->get('foo3')->configured, '->createService() calls the configurator');
        $this->assertTrue($builder->get('foo4')->configured, '->createService() calls the configurator');

        try {
            $builder->get('foo5');
            $this->fail('->createService() throws an InvalidArgumentException if the configure callable is not a valid callable');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('The configure callable for class "Bar\FooClass" is not a callable.', $e->getMessage(), '->createService() throws an InvalidArgumentException if the configure callable is not a valid callable');
        }
    }

    public function testCreateServiceWithIteratorArgument()
    {
        $builder = new ContainerBuilder();
        $builder->register('bar', 'stdClass');
        $builder
            ->register('lazy_context', 'LazyContext')
            ->setArguments(array(
                new IteratorArgument(array('k1' => new Reference('bar'), new Reference('invalid', ContainerInterface::IGNORE_ON_INVALID_REFERENCE))),
                new IteratorArgument(array()),
            ))
        ;

        $lazyContext = $builder->get('lazy_context');
        $this->assertInstanceOf(RewindableGenerator::class, $lazyContext->lazyValues);
        $this->assertInstanceOf(RewindableGenerator::class, $lazyContext->lazyEmptyValues);
        $this->assertCount(1, $lazyContext->lazyValues);
        $this->assertCount(0, $lazyContext->lazyEmptyValues);

        $i = 0;
        foreach ($lazyContext->lazyValues as $k => $v) {
            ++$i;
            $this->assertEquals('k1', $k);
            $this->assertInstanceOf('\stdClass', $v);
        }

        // The second argument should have been ignored.
        $this->assertEquals(1, $i);

        $i = 0;
        foreach ($lazyContext->lazyEmptyValues as $k => $v) {
            ++$i;
        }

        $this->assertEquals(0, $i);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCreateSyntheticService()
    {
        $builder = new ContainerBuilder();
        $builder->register('foo', 'Bar\FooClass')->setSynthetic(true);
        $builder->get('foo');
    }

    public function testCreateServiceWithExpression()
    {
        $builder = new ContainerBuilder();
        $builder->setParameter('bar', 'bar');
        $builder->register('bar', 'BarClass');
        $builder->register('foo', 'Bar\FooClass')->addArgument(array('foo' => new Expression('service("bar").foo ~ parameter("bar")')));
        $this->assertEquals('foobar', $builder->get('foo')->arguments['foo']);
    }

    public function testResolveServices()
    {
        $builder = new ContainerBuilder();
        $builder->register('foo', 'Bar\FooClass');
        $this->assertEquals($builder->get('foo'), $builder->resolveServices(new Reference('foo')), '->resolveServices() resolves service references to service instances');
        $this->assertEquals(array('foo' => array('foo', $builder->get('foo'))), $builder->resolveServices(array('foo' => array('foo', new Reference('foo')))), '->resolveServices() resolves service references to service instances in nested arrays');
        $this->assertEquals($builder->get('foo'), $builder->resolveServices(new Expression('service("foo")')), '->resolveServices() resolves expressions');
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\RuntimeException
     * @expectedExceptionMessage Constructing service "foo" from a parent definition is not supported at build time.
     */
    public function testResolveServicesWithDecoratedDefinition()
    {
        $builder = new ContainerBuilder();
        $builder->setDefinition('grandpa', new Definition('stdClass'));
        $builder->setDefinition('parent', new ChildDefinition('grandpa'));
        $builder->setDefinition('foo', new ChildDefinition('parent'));

        $builder->get('foo');
    }

    public function testResolveServicesWithCustomDefinitionClass()
    {
        $builder = new ContainerBuilder();
        $builder->setDefinition('foo', new CustomDefinition('stdClass'));

        $this->assertInstanceOf('stdClass', $builder->get('foo'));
    }

    public function testMerge()
    {
        $container = new ContainerBuilder(new ParameterBag(array('bar' => 'foo')));
        $container->setResourceTracking(false);
        $config = new ContainerBuilder(new ParameterBag(array('foo' => 'bar')));
        $container->merge($config);
        $this->assertEquals(array('bar' => 'foo', 'foo' => 'bar'), $container->getParameterBag()->all(), '->merge() merges current parameters with the loaded ones');

        $container = new ContainerBuilder(new ParameterBag(array('bar' => 'foo')));
        $container->setResourceTracking(false);
        $config = new ContainerBuilder(new ParameterBag(array('foo' => '%bar%')));
        $container->merge($config);
        $container->compile();
        $this->assertEquals(array('bar' => 'foo', 'foo' => 'foo'), $container->getParameterBag()->all(), '->merge() evaluates the values of the parameters towards already defined ones');

        $container = new ContainerBuilder(new ParameterBag(array('bar' => 'foo')));
        $container->setResourceTracking(false);
        $config = new ContainerBuilder(new ParameterBag(array('foo' => '%bar%', 'baz' => '%foo%')));
        $container->merge($config);
        $container->compile();
        $this->assertEquals(array('bar' => 'foo', 'foo' => 'foo', 'baz' => 'foo'), $container->getParameterBag()->all(), '->merge() evaluates the values of the parameters towards already defined ones');

        $container = new ContainerBuilder();
        $container->setResourceTracking(false);
        $container->register('foo', 'Bar\FooClass');
        $container->register('bar', 'BarClass');
        $config = new ContainerBuilder();
        $config->setDefinition('baz', new Definition('BazClass'));
        $config->setAlias('alias_for_foo', 'foo');
        $container->merge($config);
        $this->assertEquals(array('service_container', 'foo', 'bar', 'baz'), array_keys($container->getDefinitions()), '->merge() merges definitions already defined ones');

        $aliases = $container->getAliases();
        $this->assertTrue(isset($aliases['alias_for_foo']));
        $this->assertEquals('foo', (string) $aliases['alias_for_foo']);

        $container = new ContainerBuilder();
        $container->setResourceTracking(false);
        $container->register('foo', 'Bar\FooClass');
        $config->setDefinition('foo', new Definition('BazClass'));
        $container->merge($config);
        $this->assertEquals('BazClass', $container->getDefinition('foo')->getClass(), '->merge() overrides already defined services');

        $container = new ContainerBuilder();
        $bag = new EnvPlaceholderParameterBag();
        $bag->get('env(Foo)');
        $config = new ContainerBuilder($bag);
        $this->assertSame(array('%env(Bar)%'), $config->resolveEnvPlaceholders(array($bag->get('env(Bar)'))));
        $container->merge($config);
        $this->assertEquals(array('Foo' => 0, 'Bar' => 1), $container->getEnvCounters());

        $container = new ContainerBuilder();
        $config = new ContainerBuilder();
        $childDefA = $container->registerForAutoconfiguration('AInterface');
        $childDefB = $config->registerForAutoconfiguration('BInterface');
        $container->merge($config);
        $this->assertSame(array('AInterface' => $childDefA, 'BInterface' => $childDefB), $container->getAutoconfiguredInstanceof());
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @expectedExceptionMessage "AInterface" has already been autoconfigured and merge() does not support merging autoconfiguration for the same class/interface.
     */
    public function testMergeThrowsExceptionForDuplicateAutomaticInstanceofDefinitions()
    {
        $container = new ContainerBuilder();
        $config = new ContainerBuilder();
        $container->registerForAutoconfiguration('AInterface');
        $config->registerForAutoconfiguration('AInterface');
        $container->merge($config);
    }

    public function testResolveEnvValues()
    {
        $_ENV['DUMMY_ENV_VAR'] = 'du%%y';

        $container = new ContainerBuilder();
        $container->setParameter('bar', '%% %env(DUMMY_ENV_VAR)%');

        $this->assertSame('%% du%%%%y', $container->resolveEnvPlaceholders('%bar%', true));

        unset($_ENV['DUMMY_ENV_VAR']);
    }

    public function testCompileWithResolveEnv()
    {
        $_ENV['DUMMY_ENV_VAR'] = 'du%%y';

        $container = new ContainerBuilder();
        $container->setParameter('env(FOO)', 'Foo');
        $container->setParameter('bar', '%% %env(DUMMY_ENV_VAR)%');
        $container->setParameter('foo', '%env(FOO)%');
        $container->compile(true);

        $this->assertSame('% du%%y', $container->getParameter('bar'));
        $this->assertSame('Foo', $container->getParameter('foo'));

        unset($_ENV['DUMMY_ENV_VAR']);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\EnvNotFoundException
     * @expectedExceptionMessage Environment variable not found: "FOO".
     */
    public function testCompileWithResolveMissingEnv()
    {
        $container = new ContainerBuilder();
        $container->setParameter('foo', '%env(FOO)%');
        $container->compile(true);
    }

    /**
     * @expectedException \LogicException
     */
    public function testMergeLogicException()
    {
        $container = new ContainerBuilder();
        $container->setResourceTracking(false);
        $container->compile();
        $container->merge(new ContainerBuilder());
    }

    public function testfindTaggedServiceIds()
    {
        $builder = new ContainerBuilder();
        $builder
            ->register('foo', 'Bar\FooClass')
            ->addTag('foo', array('foo' => 'foo'))
            ->addTag('bar', array('bar' => 'bar'))
            ->addTag('foo', array('foofoo' => 'foofoo'))
        ;
        $this->assertEquals($builder->findTaggedServiceIds('foo'), array(
            'foo' => array(
                array('foo' => 'foo'),
                array('foofoo' => 'foofoo'),
            ),
        ), '->findTaggedServiceIds() returns an array of service ids and its tag attributes');
        $this->assertEquals(array(), $builder->findTaggedServiceIds('foobar'), '->findTaggedServiceIds() returns an empty array if there is annotated services');
    }

    public function testFindUnusedTags()
    {
        $builder = new ContainerBuilder();
        $builder
            ->register('foo', 'Bar\FooClass')
            ->addTag('kernel.event_listener', array('foo' => 'foo'))
            ->addTag('kenrel.event_listener', array('bar' => 'bar'))
        ;
        $builder->findTaggedServiceIds('kernel.event_listener');
        $this->assertEquals(array('kenrel.event_listener'), $builder->findUnusedTags(), '->findUnusedTags() returns an array with unused tags');
    }

    public function testFindDefinition()
    {
        $container = new ContainerBuilder();
        $container->setDefinition('foo', $definition = new Definition('Bar\FooClass'));
        $container->setAlias('bar', 'foo');
        $container->setAlias('foobar', 'bar');
        $this->assertEquals($definition, $container->findDefinition('foobar'), '->findDefinition() returns a Definition');
    }

    public function testAddObjectResource()
    {
        $container = new ContainerBuilder();

        $container->setResourceTracking(false);
        $container->addObjectResource(new \BarClass());

        $this->assertEmpty($container->getResources(), 'No resources get registered without resource tracking');

        $container->setResourceTracking(true);
        $container->addObjectResource(new \BarClass());

        $resources = $container->getResources();

        $this->assertCount(2, $resources, '2 resources were registered');

        /* @var $resource \Symfony\Component\Config\Resource\FileResource */
        $resource = end($resources);

        $this->assertInstanceOf('Symfony\Component\Config\Resource\FileResource', $resource);
        $this->assertSame(realpath(__DIR__.'/Fixtures/includes/classes.php'), realpath($resource->getResource()));
    }

    /**
     * @group legacy
     */
    public function testAddClassResource()
    {
        $container = new ContainerBuilder();

        $container->setResourceTracking(false);
        $container->addClassResource(new \ReflectionClass('BarClass'));

        $this->assertEmpty($container->getResources(), 'No resources get registered without resource tracking');

        $container->setResourceTracking(true);
        $container->addClassResource(new \ReflectionClass('BarClass'));

        $resources = $container->getResources();

        $this->assertCount(2, $resources, '2 resources were registered');

        /* @var $resource \Symfony\Component\Config\Resource\FileResource */
        $resource = end($resources);

        $this->assertInstanceOf('Symfony\Component\Config\Resource\FileResource', $resource);
        $this->assertSame(realpath(__DIR__.'/Fixtures/includes/classes.php'), realpath($resource->getResource()));
    }

    public function testGetReflectionClass()
    {
        $container = new ContainerBuilder();

        $container->setResourceTracking(false);
        $r1 = $container->getReflectionClass('BarClass');

        $this->assertEmpty($container->getResources(), 'No resources get registered without resource tracking');

        $container->setResourceTracking(true);
        $r2 = $container->getReflectionClass('BarClass');
        $r3 = $container->getReflectionClass('BarClass');

        $this->assertNull($container->getReflectionClass('BarMissingClass'));

        $this->assertEquals($r1, $r2);
        $this->assertSame($r2, $r3);

        $resources = $container->getResources();

        $this->assertCount(3, $resources, '3 resources were registered');

        $this->assertSame('reflection.BarClass', (string) $resources[1]);
        $this->assertSame('BarMissingClass', (string) end($resources));
    }

    public function testCompilesClassDefinitionsOfLazyServices()
    {
        $container = new ContainerBuilder();

        $this->assertEmpty($container->getResources(), 'No resources get registered without resource tracking');

        $container->register('foo', 'BarClass');
        $container->getDefinition('foo')->setLazy(true);

        $container->compile();

        $matchingResources = array_filter(
            $container->getResources(),
            function (ResourceInterface $resource) {
                return 'reflection.BarClass' === (string) $resource;
            }
        );

        $this->assertNotEmpty($matchingResources);
    }

    public function testResources()
    {
        $container = new ContainerBuilder();
        $container->addResource($a = new FileResource(__DIR__.'/Fixtures/xml/services1.xml'));
        $container->addResource($b = new FileResource(__DIR__.'/Fixtures/xml/services2.xml'));
        $resources = array();
        foreach ($container->getResources() as $resource) {
            if (false === strpos($resource, '.php')) {
                $resources[] = $resource;
            }
        }
        $this->assertEquals(array($a, $b), $resources, '->getResources() returns an array of resources read for the current configuration');
        $this->assertSame($container, $container->setResources(array()));
        $this->assertEquals(array(), $container->getResources());
    }

    public function testFileExists()
    {
        $container = new ContainerBuilder();
        $A = new ComposerResource();
        $a = new FileResource(__DIR__.'/Fixtures/xml/services1.xml');
        $b = new FileResource(__DIR__.'/Fixtures/xml/services2.xml');
        $c = new DirectoryResource($dir = dirname($b));

        $this->assertTrue($container->fileExists((string) $a) && $container->fileExists((string) $b) && $container->fileExists($dir));

        $resources = array();
        foreach ($container->getResources() as $resource) {
            if (false === strpos($resource, '.php')) {
                $resources[] = $resource;
            }
        }

        $this->assertEquals(array($A, $a, $b, $c), $resources, '->getResources() returns an array of resources read for the current configuration');
    }

    public function testExtension()
    {
        $container = new ContainerBuilder();
        $container->setResourceTracking(false);

        $container->registerExtension($extension = new \ProjectExtension());
        $this->assertTrue($container->getExtension('project') === $extension, '->registerExtension() registers an extension');

        $this->{method_exists($this, $_ = 'expectException') ? $_ : 'setExpectedException'}('LogicException');
        $container->getExtension('no_registered');
    }

    public function testRegisteredButNotLoadedExtension()
    {
        $extension = $this->getMockBuilder('Symfony\\Component\\DependencyInjection\\Extension\\ExtensionInterface')->getMock();
        $extension->expects($this->once())->method('getAlias')->will($this->returnValue('project'));
        $extension->expects($this->never())->method('load');

        $container = new ContainerBuilder();
        $container->setResourceTracking(false);
        $container->registerExtension($extension);
        $container->compile();
    }

    public function testRegisteredAndLoadedExtension()
    {
        $extension = $this->getMockBuilder('Symfony\\Component\\DependencyInjection\\Extension\\ExtensionInterface')->getMock();
        $extension->expects($this->exactly(2))->method('getAlias')->will($this->returnValue('project'));
        $extension->expects($this->once())->method('load')->with(array(array('foo' => 'bar')));

        $container = new ContainerBuilder();
        $container->setResourceTracking(false);
        $container->registerExtension($extension);
        $container->loadFromExtension('project', array('foo' => 'bar'));
        $container->compile();
    }

    public function testPrivateServiceUser()
    {
        $fooDefinition = new Definition('BarClass');
        $fooUserDefinition = new Definition('BarUserClass', array(new Reference('bar')));
        $container = new ContainerBuilder();
        $container->setResourceTracking(false);

        $fooDefinition->setPublic(false);

        $container->addDefinitions(array(
            'bar' => $fooDefinition,
            'bar_user' => $fooUserDefinition,
        ));

        $container->compile();
        $this->assertInstanceOf('BarClass', $container->get('bar_user')->bar);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testThrowsExceptionWhenSetServiceOnACompiledContainer()
    {
        $container = new ContainerBuilder();
        $container->setResourceTracking(false);
        $container->setDefinition('a', new Definition('stdClass'));
        $container->compile();
        $container->set('a', new \stdClass());
    }

    public function testThrowsExceptionWhenAddServiceOnACompiledContainer()
    {
        $container = new ContainerBuilder();
        $container->compile();
        $container->set('a', $foo = new \stdClass());
        $this->assertSame($foo, $container->get('a'));
    }

    public function testNoExceptionWhenSetSyntheticServiceOnACompiledContainer()
    {
        $container = new ContainerBuilder();
        $def = new Definition('stdClass');
        $def->setSynthetic(true);
        $container->setDefinition('a', $def);
        $container->compile();
        $container->set('a', $a = new \stdClass());
        $this->assertEquals($a, $container->get('a'));
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testThrowsExceptionWhenSetDefinitionOnACompiledContainer()
    {
        $container = new ContainerBuilder();
        $container->setResourceTracking(false);
        $container->compile();
        $container->setDefinition('a', new Definition());
    }

    public function testExtensionConfig()
    {
        $container = new ContainerBuilder();

        $configs = $container->getExtensionConfig('foo');
        $this->assertEmpty($configs);

        $first = array('foo' => 'bar');
        $container->prependExtensionConfig('foo', $first);
        $configs = $container->getExtensionConfig('foo');
        $this->assertEquals(array($first), $configs);

        $second = array('ding' => 'dong');
        $container->prependExtensionConfig('foo', $second);
        $configs = $container->getExtensionConfig('foo');
        $this->assertEquals(array($second, $first), $configs);
    }

    public function testAbstractAlias()
    {
        $container = new ContainerBuilder();

        $abstract = new Definition('AbstractClass');
        $abstract->setAbstract(true);

        $container->setDefinition('abstract_service', $abstract);
        $container->setAlias('abstract_alias', 'abstract_service');

        $container->compile();

        $this->assertSame('abstract_service', (string) $container->getAlias('abstract_alias'));
    }

    public function testLazyLoadedService()
    {
        $loader = new ClosureLoader($container = new ContainerBuilder());
        $loader->load(function (ContainerBuilder $container) {
            $container->set('a', new \BazClass());
            $definition = new Definition('BazClass');
            $definition->setLazy(true);
            $container->setDefinition('a', $definition);
        });

        $container->setResourceTracking(true);

        $container->compile();

        $r = new \ReflectionProperty($container, 'resources');
        $r->setAccessible(true);
        $resources = $r->getValue($container);

        $classInList = false;
        foreach ($resources as $resource) {
            if ('reflection.BazClass' === (string) $resource) {
                $classInList = true;
                break;
            }
        }

        $this->assertTrue($classInList);
    }

    public function testInitializePropertiesBeforeMethodCalls()
    {
        $container = new ContainerBuilder();
        $container->register('foo', 'stdClass');
        $container->register('bar', 'MethodCallClass')
            ->setProperty('simple', 'bar')
            ->setProperty('complex', new Reference('foo'))
            ->addMethodCall('callMe');

        $container->compile();

        $this->assertTrue($container->get('bar')->callPassed(), '->compile() initializes properties before method calls');
    }

    public function testAutowiring()
    {
        $container = new ContainerBuilder();

        $container->register(A::class);
        $bDefinition = $container->register('b', __NAMESPACE__.'\B');
        $bDefinition->setAutowired(true);

        $container->compile();

        $this->assertEquals(A::class, (string) $container->getDefinition('b')->getArgument(0));
    }

    public function testClassFromId()
    {
        $container = new ContainerBuilder();

        $unknown = $container->register('Acme\UnknownClass');
        $autoloadClass = $container->register(CaseSensitiveClass::class);
        $container->compile();

        $this->assertSame('Acme\UnknownClass', $unknown->getClass());
        $this->assertEquals(CaseSensitiveClass::class, $autoloadClass->getClass());
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\RuntimeException
     * @expectedExceptionMessage The definition for "DateTime" has no class attribute, and appears to reference a class or interface in the global namespace.
     */
    public function testNoClassFromGlobalNamespaceClassId()
    {
        $container = new ContainerBuilder();

        $definition = $container->register(\DateTime::class);
        $container->compile();
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\RuntimeException
     * @expectedExceptionMessage The definition for "123_abc" has no class.
     */
    public function testNoClassFromNonClassId()
    {
        $container = new ContainerBuilder();

        $definition = $container->register('123_abc');
        $container->compile();
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\RuntimeException
     * @expectedExceptionMessage The definition for "\foo" has no class.
     */
    public function testNoClassFromNsSeparatorId()
    {
        $container = new ContainerBuilder();

        $definition = $container->register('\\foo');
        $container->compile();
    }

    public function testServiceLocator()
    {
        $container = new ContainerBuilder();
        $container->register('foo_service', ServiceLocator::class)
            ->addArgument(array(
                'bar' => new ServiceClosureArgument(new Reference('bar_service')),
                'baz' => new ServiceClosureArgument(new TypedReference('baz_service', 'stdClass')),
            ))
        ;
        $container->register('bar_service', 'stdClass')->setArguments(array(new Reference('baz_service')));
        $container->register('baz_service', 'stdClass')->setPublic(false);
        $container->compile();

        $this->assertInstanceOf(ServiceLocator::class, $foo = $container->get('foo_service'));
        $this->assertSame($container->get('bar_service'), $foo->get('bar'));
    }

    public function testRegisterForAutoconfiguration()
    {
        $container = new ContainerBuilder();
        $childDefA = $container->registerForAutoconfiguration('AInterface');
        $childDefB = $container->registerForAutoconfiguration('BInterface');
        $this->assertSame(array('AInterface' => $childDefA, 'BInterface' => $childDefB), $container->getAutoconfiguredInstanceof());

        // when called multiple times, the same instance is returned
        $this->assertSame($childDefA, $container->registerForAutoconfiguration('AInterface'));
    }

    /**
     * This test checks the trigger of a deprecation note and should not be removed in major releases.
     *
     * @group legacy
     * @expectedDeprecation The "foo" service is deprecated. You should stop using it, as it will soon be removed.
     */
    public function testPrivateServiceTriggersDeprecation()
    {
        $container = new ContainerBuilder();
        $container->register('foo', 'stdClass')
            ->setPublic(false)
            ->setDeprecated(true);
        $container->register('bar', 'stdClass')
            ->setPublic(true)
            ->setProperty('foo', new Reference('foo'));

        $container->compile();

        $container->get('bar');
    }
}

class FooClass
{
}

class A
{
}

class B
{
    public function __construct(A $a)
    {
    }
}
