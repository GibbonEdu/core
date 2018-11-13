<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Config\Tests\Loader;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\Loader;

class LoaderTest extends TestCase
{
    public function testGetSetResolver()
    {
        $resolver = $this->getMockBuilder('Symfony\Component\Config\Loader\LoaderResolverInterface')->getMock();

        $loader = new ProjectLoader1();
        $loader->setResolver($resolver);

        $this->assertSame($resolver, $loader->getResolver(), '->setResolver() sets the resolver loader');
    }

    public function testResolve()
    {
        $resolvedLoader = $this->getMockBuilder('Symfony\Component\Config\Loader\LoaderInterface')->getMock();

        $resolver = $this->getMockBuilder('Symfony\Component\Config\Loader\LoaderResolverInterface')->getMock();
        $resolver->expects($this->once())
            ->method('resolve')
            ->with('foo.xml')
            ->will($this->returnValue($resolvedLoader));

        $loader = new ProjectLoader1();
        $loader->setResolver($resolver);

        $this->assertSame($loader, $loader->resolve('foo.foo'), '->resolve() finds a loader');
        $this->assertSame($resolvedLoader, $loader->resolve('foo.xml'), '->resolve() finds a loader');
    }

    /**
     * @expectedException \Symfony\Component\Config\Exception\FileLoaderLoadException
     */
    public function testResolveWhenResolverCannotFindLoader()
    {
        $resolver = $this->getMockBuilder('Symfony\Component\Config\Loader\LoaderResolverInterface')->getMock();
        $resolver->expects($this->once())
            ->method('resolve')
            ->with('FOOBAR')
            ->will($this->returnValue(false));

        $loader = new ProjectLoader1();
        $loader->setResolver($resolver);

        $loader->resolve('FOOBAR');
    }

    public function testImport()
    {
        $resolvedLoader = $this->getMockBuilder('Symfony\Component\Config\Loader\LoaderInterface')->getMock();
        $resolvedLoader->expects($this->once())
            ->method('load')
            ->with('foo')
            ->will($this->returnValue('yes'));

        $resolver = $this->getMockBuilder('Symfony\Component\Config\Loader\LoaderResolverInterface')->getMock();
        $resolver->expects($this->once())
            ->method('resolve')
            ->with('foo')
            ->will($this->returnValue($resolvedLoader));

        $loader = new ProjectLoader1();
        $loader->setResolver($resolver);

        $this->assertEquals('yes', $loader->import('foo'));
    }

    public function testImportWithType()
    {
        $resolvedLoader = $this->getMockBuilder('Symfony\Component\Config\Loader\LoaderInterface')->getMock();
        $resolvedLoader->expects($this->once())
            ->method('load')
            ->with('foo', 'bar')
            ->will($this->returnValue('yes'));

        $resolver = $this->getMockBuilder('Symfony\Component\Config\Loader\LoaderResolverInterface')->getMock();
        $resolver->expects($this->once())
            ->method('resolve')
            ->with('foo', 'bar')
            ->will($this->returnValue($resolvedLoader));

        $loader = new ProjectLoader1();
        $loader->setResolver($resolver);

        $this->assertEquals('yes', $loader->import('foo', 'bar'));
    }
}

class ProjectLoader1 extends Loader
{
    public function load($resource, $type = null)
    {
    }

    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'foo' === pathinfo($resource, PATHINFO_EXTENSION);
    }

    public function getType()
    {
    }
}
