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

namespace Library\Forms;

use Library\Forms\FormFactory;

/**
 * Row
 *
 * @version v14
 * @since   v14
 */
class Row
{
    protected $id = '';
    protected $class = '';

    protected $formFactory;
    protected $formElements = array();

    public function __construct(FormFactory $formFactory, $id = '')
    {
        $this->formFactory = $formFactory;
        $this->id = $id;
    }

    public function __call($function, $args)
    {
        if (stripos($function, 'add') === false) {
            return;
        }

        $element = null;
        try {
            $function = str_replace('add', 'create', $function);

            if (method_exists($this->formFactory, $function)) {
                $element = call_user_func_array(array($this->formFactory, $function), $args);
                $this->addElement($element, isset($args[0])? $args[0] : '');
            }

        } catch (Exception $e) {
        }
        return $element;
    }

    public function addLabel($for, $label)
    {
        return $this->addElement($this->formFactory->createLabel($this, $for, $label));
    }

    public function addElement(FormElementInterface $element, $id = '')
    {
        //if (empty($id)) {
            $id = 'element-'.count($this->formElements);
        //}

        $this->formElements[$id] = $element;
        return $element;
    }

    public function getElement($id = '')
    {
        if (empty($this->formElements) || count($this->formElements) == 1) {
            return null;
        }
        return (isset($this->formElements[$id]))? $this->formElements[$id] : end($this->formElements);
    }

    public function addClass($value = '')
    {
        if (empty($this->class)) return $this->setClass($value);

        $this->class .= ' '.$value;
        return $this;
    }

    public function setClass($value = '')
    {
        $this->class = $value;
        return $this;
    }

    public function getID()
    {
        return $this->id;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function getElements()
    {
        return $this->formElements;
    }

    public function getElementCount()
    {
        return count($this->formElements);
    }

    public function isLastElement($element)
    {
        return (end($this->formElements) == $element);
    }
}
