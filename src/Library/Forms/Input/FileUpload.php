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
class FileUpload extends Element {

	protected $accepts = array();

	public function accepts($value) {
		if (is_string($value)) {
			$value = explode(',', $value);
		}
		$this->accepts = $value;

		if (!empty($this->accepts) && is_array($this->accepts)) {
			$within = implode(',', array_map(function($str) { return sprintf("'%s'", $str); }, $this->accepts));
			$this->addValidation('Validate.Inclusion', 'within: ['.$within.'], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false');
		}
		return $this;
	}

	protected function getElement() {

		$output = '<input type="file" class="'.$this->class.'" id="'.$this->name.'" name="'.$this->name.'" ';

		if (!empty($this->accepts) && is_array($this->accepts)) {
			$output .= ' accepts="'.implode(',', $this->accepts).'"';
		}

		$output .= '>';

		return $output;
	}
}
