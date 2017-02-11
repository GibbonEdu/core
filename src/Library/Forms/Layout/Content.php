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

use \Library\Forms\FormElementInterface as FormElementInterface;

/**
 * Content
 *
 * @version	v14
 * @since	v14
 */
class Content implements FormElementInterface {

	protected $content;
	protected $class = '';

	public function __construct($content) {
		$this->content = $content;
	}

	public function prepend($value) {
		$this->content = $value.$this->content;
		return $this;
	}

	public function append($value) {
		$this->content .= $value;
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

	public function getClass() {
		return $this->class;
	}

	public function getOutput() {
		return $this->content;
	}

	public function getValidation() {
		return '';
	}
}