<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\CachePoolPass;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class CachePoolPassTest extends TestCase
{
    private $cachePoolPass;

    protected function setUp()
    {
        $this->cachePoolPass = new CachePoolPass();
    }

    public function testNamespaceArgumentIsReplaced()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.name', 'app');
        $container->setParameter('kernel.environment', 'prod');
        $container->setParameter('kernel.root_dir', 'foo');
        $adapter = new Definition();
        $adapter->setAbstract(true);
        $adapter->addTag('cache.pool');
        $container->setDefinition('app.cache_adapter', $adapter);
        $container->setAlias('app.cache_adapter_alias', 'app.cache_adapter');
        $cachePool = new ChildDefinition('app.cache_adapter_alias');
        $cachePool->addArgument(null);
        $cachePool->addTag('cache.pool');
        $container->setDefinition('app.cache_pool', $cachePool);

        $this->cachePoolPass->process($container);

        $this->assertSame('D07rhFx97S', $cachePool->getArgument(0));
    }

    public function testNamespaceArgumentIsNotReplacedIfArrayAdapterIsUsed()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');
        $container->setParameter('kernel.name', 'app');
        $container->setParameter('kernel.root_dir', 'foo');

        $container->register('cache.adapter.array', ArrayAdapter::class)->addArgument(0);

        $cachePool = new ChildDefinition('cache.adapter.array');
        $cachePool->addTag('cache.pool');
        $container->setDefinition('app.cache_pool', $cachePool);

        $this->cachePoolPass->process($container);

        $this->assertCount(0, $container->getDefinition('app.cache_pool')->getArguments());
    }

    public function testArgsAreReplaced()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.name', 'app');
        $container->setParameter('kernel.environment', 'prod');
        $container->setParameter('cache.prefix.seed', 'foo');
        $cachePool = new Definition();
        $cachePool->addTag('cache.pool', array(
            'provider' => 'foobar',
            'default_lifetime' => 3,
        ));
        $cachePool->addArgument(null);
        $cachePool->addArgument(null);
        $cachePool->addArgument(null);
        $container->setDefinition('app.cache_pool', $cachePool);

        $this->cachePoolPass->process($container);

        $this->assertInstanceOf(Reference::class, $cachePool->getArgument(0));
        $this->assertSame('foobar', (string) $cachePool->getArgument(0));
        $this->assertSame('itantF+pIq', $cachePool->getArgument(1));
        $this->assertSame(3, $cachePool->getArgument(2));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid "cache.pool" tag for service "app.cache_pool": accepted attributes are
     */
    public function testThrowsExceptionWhenCachePoolTagHasUnknownAttributes()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.name', 'app');
        $container->setParameter('kernel.environment', 'prod');
        $container->setParameter('kernel.root_dir', 'foo');
        $adapter = new Definition();
        $adapter->setAbstract(true);
        $adapter->addTag('cache.pool');
        $container->setDefinition('app.cache_adapter', $adapter);
        $cachePool = new ChildDefinition('app.cache_adapter');
        $cachePool->addTag('cache.pool', array('foobar' => 123));
        $container->setDefinition('app.cache_pool', $cachePool);

        $this->cachePoolPass->process($container);
    }
}
