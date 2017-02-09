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

namespace Library;

/**
 * Form
 *
 * Responsibilities:
 *
 * @version	v14
 * @since	v14
 */
class FormElement implements iFormElement {

	protected $name;
	protected $label;
	protected $description;
	protected $class;
	protected $value;

	protected $required = false;
	protected $fullWidth = false;

	protected $validation = array();

	public function __construct($name, $label) {
		$this->name = $name;
		$this->label = $label;
		$this->class = 'standardWidth';
	}

	public function isRequired($value = true) {
		$this->required = $value;
		return $this;
	}

	public function setDescription($value = '') {
		$this->description = $value;
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

	public function fullWidth($value = true) {
		$this->fullWidth = $value;
		$this->class = 'fullWidth';
		return $this;
	}

	public function addValidation($type, $params = '') {
		$this->validation[$type] = $params;
		return $this;
	}

	protected function getElement() {
		return $this->value;
	}

	protected function getLabel() {
		if (empty($this->label)) return '';

		return'<b>'.__($this->label).' '.( ($this->required)? '*' : '').'</b><br/>';
	}

	protected function getDescription() {
		if (empty($this->description)) return '';

		return '<span class="emphasis small">'.__($this->description).'</span><br/>';
	}

	public function getOutput() {
		$output = '';

		$output .= '<tr id="'.$this->name.'-row">';

			$output .= '<td style="width: 275px">';
				$output .= $this->getLabel();
				$output .= $this->getDescription();
			$output .= '</td>';

			$output .= '<td class="right">';
				$output .= $this->getElement();
			$output .= '</td>';
		$output .= '</tr>';

		return $output;
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