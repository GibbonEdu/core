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

    protected $factory;
    protected $renderer;

    protected $rows = array();
    protected $triggers = array();
    protected $values = array();

    /**
     * Create a form with a specific factory and renderer.
     * @param    FormFactoryInterface   $factory
     * @param    FormRendererInterface  $renderer
     * @param    string                 $action
     * @param    string                 $method
     */
    public function __construct(FormFactoryInterface $factory, FormRendererInterface $renderer, $action, $method)
    {
        $this->factory = $factory;
        $this->renderer = $renderer;
        $this->setAttribute('action', ltrim($action, '/'));
        $this->setAttribute('method', $method);
        $this->setAttribute('autocomplete', 'on');
        $this->setAttribute('enctype', 'multipart/form-data');
    }

    /**
     * Create a form with the default factory and renderer.
     * @param    string  $id
     * @param    string  $action
     * @param    string  $method
     * @param    string  $class
     * @return   object  Form object
     */
    public static function create($id, $action, $method = 'post', $class = 'smallIntBorder fullWidth standardForm')
    {
        $factory = FormFactory::create();
        $renderer = FormRenderer::create();

        $form = new Form($factory, $renderer, $action, $method);

        $form->setID($id);
        $form->setClass($class);

        return $form;
    }

    /**
     * Get the current factory.
     * @return  object FormFactoryInterface
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * Set the factory.
     * @param  FormFactoryInterface  $factory
     */
    public function setFactory(FormFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Get the current renderer.
     * @return  object FormRendererInterface
     */
    public function getRenderer()
    {
        return $this->renderer;
    }

    /**
     * Set the renderer.
     * @param  FormRendererInterface  $renderer
     */
    public function setRenderer(FormRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Get the current HTTP method for this form (default: post)
     * @return  string
     */
    public function getMethod()
    {
        return $this->getAttribute('method');
    }

    /**
     * Get the current action URL for the form.
     * @return  string
     */
    public function getAction()
    {
        return $this->getAttribute('action');
    }

    /**
     * Adds a Row object to the form and returns it.
     * @param  string  $id
     * @return object Row
     */
    public function addRow($id = '')
    {
        $row = $this->factory->createRow($id);
        $this->rows[] = $row;

        return $row;
    }

    /**
     * Cet the last added Row object, null if none exist.
     * @return  object|null
     */
    public function getRow()
    {
        return (!empty($this->rows))? end($this->rows) : null;
    }

    /**
     * Get an array of all Row objects in the form.
     * @return  array
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * Adds an input type=hidden value to the form.
     * @param  string  $name
     * @param  string  $value
     */
    public function addHiddenValue($name, $value)
    {
        $this->values[] = array('name' => $name, 'value' => $value);
    }

    /**
     * Get an array of all hidden values.
     * @return  array
     */
    public function getHiddenValues()
    {
        return $this->values;
    }

    /**
     * Get the value of the autocomplete HTML form attribute.
     * @return  string
     */
    public function getAutocomplete()
    {
        return $this->getAttribute('autocomplete');
    }

    /**
     * Turn autocomplete for the form On or Off.
     * @param  string  $value
     * @return self
     */
    public function setAutocomplete($value)
    {
        $this->setAttribute('autocomplete', $value);

        return $this;
    }

    /**
     * Add a confirmation message to display before form submission.
     * @param string $message
     * @return self
     */
    public function addConfirmation($message)
    {
        $this->setAttribute('onsubmit', "return confirm(\"".__($message)."\")");
        
        return $this;
    }

    /**
     * Adds a Trigger object that injects javascript to respond to form events.
     * @param  string  $selector
     * @param  object  $trigger
     */
    public function addTrigger($selector, $trigger)
    {
        $this->triggers[$selector] = $trigger;

        return $trigger;
    }

    /**
     * Get an array of all Trigger objects.
     * @return  array
     */
    public function getTriggers()
    {
        return $this->triggers;
    }

    /**
     * Adds a visibility trigger to the form by class name.
     * @param   string  $class Element name
     * @return  object Trigger
     */
    public function toggleVisibilityByClass($class)
    {
        $selector = '.'.$class;

        return $this->addTrigger($selector, $this->factory->createTrigger($selector));
    }

    /**
     * Adds a visibility trigger to the form by element ID.
     * @param   string  $id CSS Element ID
     * @return  object Trigger
     */
    public function toggleVisibilityByID($id)
    {
        $selector = '#'.$id;

        return $this->addTrigger($selector, $this->factory->createTrigger($selector));
    }

    /**
     * Loads an array of $key => $value pairs into any form elements with a matching name.
     * @param   array  &$data
     * @return  self
     */
    public function loadAllValuesFrom(&$data)
    {
        foreach ($this->getRows() as $row) {
            $row->loadFrom($data);
        }

        return $this;
    }

    /**
     * Loads the state for several form elements by calling $method with values from $data.
     * @param string $method
     * @param array $data
     * @return void
     */
    public function loadStateFrom($method, &$data)
    {
        foreach ($this->getRows() as $row) {
            $row->loadState($method, $data);
        }

        return $this;
    }

    /**
     * Renders the form to HTML.
     * @return  string
     */
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

interface RowDependancyInterface
{
    public function setRow($row);
}
