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
 * Trigger
 *
 * @version	v14
 * @since	v14
 */
class Trigger {

	protected $selector;
	protected $elementType;
	protected $elementName;
	protected $elementValue;

	public function __construct($selector) {
		$this->selector = $selector;
	}

	public function onSelect($name) {
		$this->elementType = 'select';
		$this->elementName = $name;
		return $this;
	}

	public function onCheckbox($name) {
		$this->elementType = 'input[type="checkbox"]';
		$this->elementName = $name;
		return $this;
	}

	public function onRadio($name) {
		$this->elementType = 'input[type="radio"]';
		$this->elementName = $name;
		return $this;
	}

	public function when($value) {
		$this->elementValue = $value;
		return $this;
	}

	public function getTargetSelector() {
		return $this->selector;
	}

	public function getSourceSelector() {
		return $this->elementType.'[name='.$this->elementName.']';
	}

	public function getOutput() {
		$output = '';

		$targetSelector = $this->getTargetSelector();
		$sourceSelector = $this->getSourceSelector();

		$output .= "if ($('{$sourceSelector}').val() != '{$this->elementValue}') \n";
		$output .= "{ $('{$targetSelector}').hide(); } \n\n";

		$output .= "$('{$sourceSelector}').change(function(){ \n";
			$output .= "if ($('{$sourceSelector}').val()=='{$this->elementValue}' ) { \n";
				$output .= "$('{$targetSelector}').slideDown('fast'); \n";
			$output .= "} else { \n";
				$output .= "$('{$targetSelector}').hide(); \n";
			$output .= "} \n";
		$output .= "}); \n";

		return $output;
	}
}