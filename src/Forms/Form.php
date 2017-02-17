<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Forms;

use Gibbon\Forms\FormFactory;
use Gibbon\Forms\Traits\BasicAttributesTrait;

/**
 * Form
 *
 * @version v14
 * @since   v14
 */
class Form implements OutputableInterface
{
    use BasicAttributesTrait;

    protected $action;
    protected $factory;
    protected $renderer;

    protected $rows = array();
    protected $triggers = array();
    protected $values = array();

    public function __construct(FormFactoryInterface $factory, $action)
    {
        $this->factory = $factory;
        $this->action = ltrim($action, '/');
    }

    public static function create($id, $action, $class = 'smallIntBorder fullWidth standardForm')
    {
        $form = FormFactory::create()->createForm($action);

        $form->setRenderer($form->factory->createFormRenderer());
        $form->setClass($class);
        $form->setID($id);

        return $form;
    }

    public function setFactory($factory)
    {
        $this->factory = $factory;
    }

    public function setRenderer($renderer)
    {
        $this->renderer = $renderer;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function addRow($id = '')
    {
        $row = $this->factory->createRow($id);
        $this->rows[] = $row;

        return $row;
    }

    public function getRow()
    {
        return (!empty($this->rows))? end($this->rows) : null;
    }

    public function getRows()
    {
        return $this->rows;
    }

    public function addHiddenValue($name, $value)
    {
        $this->values[$name] = $value;
    }

    public function getHiddenValues()
    {
        return $this->values;
    }

    public function addTrigger($selector, $trigger)
    {
        $this->triggers[$selector] = $trigger;

        return $trigger;
    }

    public function getTriggers()
    {
        return $this->triggers;
    }

    public function toggleVisibilityByClass($class)
    {
        $selector = '.'.$class;

        return $this->addTrigger($selector, $this->factory->createTrigger($selector));
    }

    public function toggleVisibilityByID($id)
    {
        $selector = '#'.$id;

        return $this->addTrigger($selector, $this->factory->createTrigger($selector));
    }

    public function getOutput() {
        return $this->renderer->renderForm($this);
    }
}

/**
 * Define common interfaces for elements
 *
 * @version v14
 * @since   v14
 */
interface FormFactoryInterface {
    public function createForm($action);
    public function createFormRenderer();

    public function createRow($id);
    public function createColumn($id);
    public function createTrigger($selector);
}

interface FormRendererInterface {
    public function renderForm(Form $form);
}

interface BasicAttributesInterface
{
    public function getID();
    public function getClass();
}

interface ValidatableInterface
{
    public function getValidation();
}

interface OutputableInterface
{
    public function getOutput();
}
