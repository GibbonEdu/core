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

namespace Library\Forms\Layout;

/**
 * Label
 *
 * @version	v14
 * @since	v14
 */
class Label extends Content {

	protected $row;

	protected $label;
	protected $description;
	protected $for = '';

	public function __construct( \Library\Forms\Row &$row, $for, $label) {
		$this->row = &$row;
		$this->label = $label;
		$this->for = $for;
	}

	public function description($value = '') {
		$this->description = $value;
		return $this;
	}

	public function getRequired() {
		if (empty($this->for)) return false;

		$element = $this->row->getElement($this->for);

		return (!empty($element))? $element->getRequired() : false;
	}

	public function getOutput() {
		$output = '';

		if (!empty($this->label)) {
			$output .= '<label for="'.$this->for.'"><b>'.__($this->label).' '.( ($this->getRequired())? '*' : '').'</b></label><br/>';
		}

		if (!empty($this->description)) {
			$output .= '<span class="emphasis small">'.__($this->description).'</span><br/>';
		}

		$output .= $this->content;

		return $output;
	}
}