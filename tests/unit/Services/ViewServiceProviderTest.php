<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon\Services;

use Gibbon\Forms\Form;
use Gibbon\Forms\FormFactoryInterface;
use Gibbon\Forms\View\FormRendererInterface;
use PHPUnit\Framework\TestCase;
use League\Container\Container;

/**
 * @cover ViewServiceProvider
 */
class ViewServiceProviderTest extends TestCase
{
    public function testCanProvideForm()
    {
        $container = new Container();
        $container->add('twig', $this->createMock(\Twig\Environment::class));
        $service = new ViewServiceProvider();
        $service->setContainer($container);
        $service->register();

        /**
         * @var Form $form
         */
        $form = $container->get(Form::class);
        $this->assertInstanceOf(FormRendererInterface::class, $form->getRenderer());
        $this->assertInstanceOf(FormFactoryInterface::class, $form->getFactory());
    }
}
