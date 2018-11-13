<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\DependencyInjection\FragmentRendererPass;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;

class FragmentRendererPassTest extends TestCase
{
    /**
     * Tests that content rendering not implementing FragmentRendererInterface
     * trigger an exception.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testContentRendererWithoutInterface()
    {
        // one service, not implementing any interface
        $services = array(
            'my_content_renderer' => array(array('alias' => 'foo')),
        );

        $definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')->getMock();

        $builder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')->setMethods(array('hasDefinition', 'findTaggedServiceIds', 'getDefinition'))->getMock();
        $builder->expects($this->any())
            ->method('hasDefinition')
            ->will($this->returnValue(true));

        // We don't test kernel.fragment_renderer here
        $builder->expects($this->atLeastOnce())
            ->method('findTaggedServiceIds')
            ->will($this->returnValue($services));

        $builder->expects($this->atLeastOnce())
            ->method('getDefinition')
            ->will($this->returnValue($definition));

        $pass = new FragmentRendererPass();
        $pass->process($builder);
    }

    public function testValidContentRenderer()
    {
        $services = array(
            'my_content_renderer' => array(array('alias' => 'foo')),
        );

        $renderer = new Definition('', array(null));

        $definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')->getMock();
        $definition->expects($this->atLeastOnce())
            ->method('getClass')
            ->will($this->returnValue('Symfony\Component\HttpKernel\Tests\DependencyInjection\RendererService'));

        $builder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')->setMethods(array('hasDefinition', 'findTaggedServiceIds', 'getDefinition', 'getReflectionClass'))->getMock();
        $builder->expects($this->any())
            ->method('hasDefinition')
            ->will($this->returnValue(true));

        // We don't test kernel.fragment_renderer here
        $builder->expects($this->atLeastOnce())
            ->method('findTaggedServiceIds')
            ->will($this->returnValue($services));

        $builder->expects($this->atLeastOnce())
            ->method('getDefinition')
            ->will($this->onConsecutiveCalls($renderer, $definition));

        $builder->expects($this->atLeastOnce())
            ->method('getReflectionClass')
            ->with('Symfony\Component\HttpKernel\Tests\DependencyInjection\RendererService')
            ->will($this->returnValue(new \ReflectionClass('Symfony\Component\HttpKernel\Tests\DependencyInjection\RendererService')));

        $pass = new FragmentRendererPass();
        $pass->process($builder);

        $this->assertInstanceOf(Reference::class, $renderer->getArgument(0));
    }
}

class RendererService implements FragmentRendererInterface
{
    public function render($uri, Request $request = null, array $options = array())
    {
    }

    public function getName()
    {
        return 'test';
    }
}
