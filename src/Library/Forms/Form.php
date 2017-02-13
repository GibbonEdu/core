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
 * Form
 *
 * @version	v14
 * @since	v14
 */
class Form implements FormInterface {

	protected $id;
	protected $action;
	protected $class;

	protected $formRows = array();
	protected $hiddenValues = array();

	public function __construct($id, $action, $class = 'smallIntBorder fullWidth standardForm') {
		$this->id = $id;
		$this->action = ltrim($action, '/');
		$this->class = $class;
	}

	public static function create($id, $action, $class = 'smallIntBorder fullWidth standardForm') {
		return new \Library\Forms\Form($id, $action, $class);
	}

	public function setClass($value = '') {
		$this->class = $value;
		return $this;
	}

	public function addClass($value = '') {
		$this->class .= ' '.$value;
		return $this;
	}

	public function getClass() {
		return $this->class;
	}

	public function addRow($id = '') {
		if (empty($id)) $id = 'row-'.count($this->formRows);

		$row = new \Library\Forms\Row($id);
		$this->formRows[$id] = $row;

		return $row;
	}

	public function getRow($id = '') {
		return (isset($this->formRows[$id]))? $this->formRows[$id] : end($this->formRows);
	}

	public function addHiddenValue($name, $value) {
		$this->hiddenValues[$name] = $value;
	}
	
	public function getOutput() {
		$output = '';

		$totalColumns = $this->getColumnCount($this->formRows);

		$output .= '<form id="'.$this->id.'" method="post" action="'.$this->action.'">';

			// Output hidden values
			foreach ($this->hiddenValues as $name => $value) {
				$output .= '<input name="'.$name.'" value="'.$value.'" type="hidden">';
			}

			$output .= '<table class="'.$this->class.'" cellspacing="0">';

				// Output form rows
				foreach ($this->formRows as $row) {
					$output .= '<tr id="'.$row->getID().'" class="'.$row->getClass().'">';

					// Output each element inside the row
					foreach ($row->getElements() as $element) {

						$colspan = ($row->isLastElement($element) && $row->getElementCount() < $totalColumns)? 'colspan="'.($totalColumns + 1 - $row->getElementCount()).'"' : '';

						$output .= '<td class="'.$element->getClass().'" '.$colspan.'>';
							$output .= $element->getOutput();
						$output .= '</td>';
					} 
					$output .= '</tr>';
				}

			$output .= '</table>';

			// Output the validation code, aggregated 
			$output .= '<script type="text/javascript">'."\n";

			foreach ($this->formRows as $row) {
				foreach ($row->getElements() as $element) {
					if ($element instanceof ValidateableInterface) {
						$output .= $element->getValidation();
					}
				} 
			}

			$output .= '</script>';

		$output .= '</form>';

		return $output;
	}

	protected function getColumnCount($rows) {
		$count = 0;
		foreach ($rows as $row) {
			if ($row->getElementCount() > $count) {
				$count = $row->getElementCount();
			}
		}

		return max(2, $count);
	}
}

/**
 * Define common interfaces for elements
 *
 * @version	v14
 * @since	v14
 */
interface FormInterface {
	public function addClass($value);
	public function setClass($value);
	public function getClass();

	public function addRow();
	public function getRow();

	public function addHiddenValue($name, $value);

    public function getOutput();
}

interface FormElementInterface {
	public function getClass();
    public function getOutput();
}

interface ValidateableInterface {
	public function getValidation();
}