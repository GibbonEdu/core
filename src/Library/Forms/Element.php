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

/**
 * Element
 *
 * @version	v14
 * @since	v14
 */
abstract class Element implements FormElementInterface, ValidateableInterface {

	protected $name;
	protected $class;
	protected $value;

	protected $required = false;

	protected $validation = array();

	public function __construct($name) {
		$this->name = $name;
		$this->class = 'standardWidth';
	}

	protected abstract function getElement();

	public function isRequired($value = true) {
		$this->required = $value;
		return $this;
	}

	public function addClass($value = '') {
		$this->class .= ' '.$value;
		return $this;
	}

	public function setClass($value = '') {
		$this->class = $value;
		return $this;
	}

	public function setValue($value = '') {
		$this->value = $value;
		return $this;
	}

	public function addValidation($type, $params = '') {
		$this->validation[$type] = $params;
		return $this;
	}

	public function getRequired() {
		return $this->required;
	}

	public function getClass() {
		return $this->class;
	}

	public function getOutput() {
		return $this->getElement();
	}

	public function getValidation() {
		$output = '';

		if ($this->required == true || !empty($this->validation)) {

			$output .= 'var '.$this->name.'Validate=new LiveValidation(\''.$this->name.'\'); '."\r";

			if ($this->required == true) {
				$output .= $this->name.'Validate.add(Validate.Presence); '."\r";
			}

			if (!empty($this->validation) && is_array($this->validation)) {
				foreach ($this->validation as $type => $params) {
					$output .= $this->name.'Validate.add('.$type.', {'.$params.' } ); '."\r";
				}
			}
		}

		return $output;
	}
}
