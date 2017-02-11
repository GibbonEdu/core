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

namespace Library\Forms\Input;

use \Library\Forms\Element as Element;

/**
 * Checkbox
 *
 * @version	v14
 * @since	v14
 */
class Radio extends Element {

	protected $options = array();

	public function fromString($value) {

		if (empty($value) || !is_string($value)) {
			throw new \Exception( sprintf('Radio Options %s: fromString expects value to be a string, %s given.', $this->name, gettype($value) ) );
		}

		$pieces = explode(',', $value);

		foreach ($pieces as $piece) {
			$piece = trim($piece);

			$this->options[$piece] = $piece;
		}

		return $this;
	}

	public function fromArray($value) {

		if (empty($value) || !is_array($value)) {
			throw new \Exception( sprintf('Radio Options %s: fromArray expects value to be an Array, %s given.', $this->name, gettype($value) ) );
		}

		$this->options = array_merge($this->options, $value);

		return $this;
	}

	public function checked($value) {
		$this->value = $value;
		return $this;
	}

	protected function getIsChecked($value) {
		return (!empty($value) && $value == $this->value )? 'checked' : '';
	}

	protected function getElement() {
		$output = '';

		if (!empty($this->options) && is_array($this->options)) {

			foreach ($this->options as $value => $label) {
				$output .= '<label title="'.$this->name.'" for="'.$this->name.'">'.__($label).'</label> ';
				$output .= '<input type="radio" class="'.$this->class.'" name="'.$this->name.'" value="'.$value.'" '.$this->getIsChecked($value).'><br/>';
			}

		}

		return $output;
	}
}