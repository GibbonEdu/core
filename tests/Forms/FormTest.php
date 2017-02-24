<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon\Forms;

use PHPUnit\Framework\TestCase;

/**
 * @covers Form
 */
class FormTest extends TestCase
{
    public function testCanBeCreatedStatically()
    {
        $this->assertInstanceOf(
            Form::class,
            Form::create('testID', 'testAction')
        );
    }

    public function testCanOutputToHtml()
    {
        $form = Form::create('testID', 'testAction');
        $output = $form->getOutput();

        $this->assertTrue(stripos($output, '<form') !== false);
        $this->assertTrue(stripos($output, '</form>') !== false);
    }

    public function testCanAddRow()
    {
        $form = Form::create('testID', 'testAction');

        $this->assertTrue(count($form->getRows()) == 0);
        $row = $form->addRow();

        $this->assertTrue(count($form->getRows()) > 0);
        $this->assertSame($row, $form->getRow());
    }

    public function testCanAddHiddenValue()
    {
        $form = Form::create('testID', 'testAction');

        $this->assertTrue(count($form->getHiddenValues()) == 0);
        $form->addHiddenValue('name', 'value');

        $this->assertTrue(count($form->getHiddenValues()) > 0);
    }

    public function testCanAddTrigger()
    {
        $form = Form::create('testID', 'testAction');

        $this->assertTrue(count($form->getTriggers()) == 0);
        $form->addTrigger('selector', 'trigger');

        $this->assertTrue(count($form->getTriggers()) > 0);
    }

    public function testCanSetFactory()
    {
        $form = Form::create('testID', 'testAction');

        $newFactory = FormFactory::create();
        $form->setFactory($newFactory);

        $this->assertSame($newFactory, $form->getFactory());
    }

    public function testCanSetRenderer()
    {
        $form = Form::create('testID', 'testAction');

        $newRenderer = FormRenderer::create();
        $form->setRenderer($newRenderer);

        $this->assertSame($newRenderer, $form->getRenderer());
    }

    public function testEachNewFormHasAFactory()
    {
        $form = Form::create('testID', 'testAction');

        $this->assertInstanceOf(
            FormFactoryInterface::class,
            $form->getFactory()
        );
    }

    public function testEachNewFormHasARenderer()
    {
        $form = Form::create('testID', 'testAction');

        $this->assertInstanceOf(
            FormRendererInterface::class,
            $form->getRenderer()
        );
    }

    public function testEachNewFormHasBasicAttributes()
    {
        $form = Form::create('testID', 'testAction');

        $this->assertSame('testID', $form->getID());
        $this->assertSame('testAction', $form->getAction());
        $this->assertSame('post', $form->getMethod());
    }
}
