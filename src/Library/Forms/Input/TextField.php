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
 * TextField
 *
 * @version	v14
 * @since	v14
 */
class TextField extends Element {

	protected $maxLength;

	public function maxLength($value = '') {
		$this->maxLength = $value;

		$this->addValidation('Validate.Length', '{ maximum: '.$this->maxLength.' }');
		
		return $this;
	}

	public function placeholder($value) {
		$this->placeholder = $value;

		return $this;
	}

	protected function getElement() {

		$output = '<input type="text" class="'.$this->class.'" id="'.$this->name.'" name="'.$this->name.'" value="'.$this->value.'"';

		if (!empty($this->maxLength)) {
			$output .= ' maxlength="'.$this->maxLength.'"';
		}

		if (!empty($this->placeholder)) {
			$output .= ' placeholder="'.$this->placeholder.'"';
		}

		$output .= '>';

		return $output;
	}
}