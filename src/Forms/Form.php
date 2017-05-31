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
    protected $method;
    protected $factory;
    protected $renderer;

    protected $rows = array();
    protected $triggers = array();
    protected $values = array();

    public function __construct(FormFactoryInterface $factory, FormRendererInterface $renderer, $action, $method)
    {
        $this->factory = $factory;
        $this->renderer = $renderer;
        $this->action = ltrim($action, '/');
        $this->method = $method;
    }

    public static function create($id, $action, $method = 'post', $class = 'smallIntBorder fullWidth standardForm')
    {
        $factory = FormFactory::create();
        $renderer = FormRenderer::create();

        $form = new Form($factory, $renderer, $action, $method);

        $form->setID($id);
        $form->setClass($class);

        return $form;
    }

    public function getFactory()
    {
        return $this->factory;
    }

    public function setFactory(FormFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function getRenderer()
    {
        return $this->renderer;
    }

    public function setRenderer(FormRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    public function getMethod()
    {
        return $this->method;
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
        $this->values[] = array('name' => $name, 'value' => $value);
    }

    public function getHiddenValues()
    {
        return $this->values;
    }

    public function getAutocomplete()
    {
        $autocomplete = $this->getAttribute('autocomplete');
        return (!empty($autocomplete))? $autocomplete : 'off';
    }

    public function setAutocomplete($value)
    {
        $this->setAttribute('autocomplete', $value);
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

    public function loadAllValuesFrom(&$data)
    {
        foreach ($this->getRows() as $row) {
            $row->loadFrom($data);
        }

        return $this;
    }

    public function getOutput()
    {
        return $this->renderer->renderForm($this);
    }
}

/**
 * Define common interfaces for elements. PSR-2 aside, I really hate putting things this small in their own files ...
 *
 * @version v14
 * @since   v14
 */
interface BasicAttributesInterface
{
    public function getID();
    public function getClass();
}

interface ValidatableInterface
{
    public function addValidation($name);
    public function getValidationOutput();
}

interface OutputableInterface
{
    public function getOutput();
}
