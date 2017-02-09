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
class Form_TextArea extends Form_Element {

	protected $rows = 4;

	public function setRows($count) {
		$this->rows = $count;
	}

	protected function getElement() {
		return '<textarea class="standardWidth" id="'.$this->name.'" name="'.$this->name.'" rows="'.$this->rows.'">'.$this->value.'</textarea>';
	}
}