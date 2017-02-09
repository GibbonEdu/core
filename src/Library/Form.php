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
class Form {

	protected $pdo;

	protected $id;
	protected $action;

	protected $formElements = array();
	protected $hiddenValues = array();

	public function __construct($pdo, $id, $action) {
		$this->pdo = $pdo;
		$this->id = $id;
		$this->action = $action;
	}

	/**
	 * addHiddenValue
	 * @version  v14
	 * @since    v14
	 * @param    string  $name
	 * @param    string  $value
	 */
	public function addHiddenValue($name, $value) {
		$this->hiddenValues[$name] = $value;
	}

	public function addElement($element) {
		$this->formElements[] = $element;
		return $element;
	}

	public function addGeneric($name, $label) {
		return $this->addElement( new \Library\Form_Element($name, $label) );
	}

	public function addTextField($name, $label) {
		return $this->addElement( new \Library\Form_TextField($name, $label) );
	}

	public function addTextArea($name, $label) {
		return $this->addElement( new \Library\Form_TextArea($name, $label) );
	}

	public function addSelect($name, $label) {
		return $this->addElement( new \Library\Form_Select($name, $label) );
	}

	public function addSelectSchoolYear($name, $label) {
		$sql = 'SELECT gibbonSchoolYearID as `value`, name FROM gibbonSchoolYear ORDER BY sequenceNumber';
		$results = $this->pdo->executeQuery(array(), $sql);

		return $this->addSelect($name, $label)->fromQuery($results)->placeholder('Please select...');
	}

	public function addSelectLanguage($name, $label) {
		$sql = 'SELECT name as `value`, name FROM gibbonLanguage ORDER BY name';
		$results = $this->pdo->executeQuery(array(), $sql);

		return $this->addSelect($name, $label)->fromQuery($results);
	}

	public function addYesNo($name, $label) {
		return $this->addSelect($name, $label)->fromArray( array( 'Y' => 'Yes', 'N' => 'No') );
	}

	public function addHeading($label) {
		$content = sprintf('<h3>%s</h3>', $label);
		return $this->addElement( new \Library\Form_Layout($content) )->setClass('break');
	}

	public function addSubheading($label) {
		$content = sprintf('<h4>%s</h4>', $label);
		return $this->addElement( new \Library\Form_Layout($content) );
	}

	public function addSection($content) {
		return $this->addElement( new \Library\Form_Layout($content) );
	}

	public function addAlert($content, $level = 'warning') {
		$content = sprintf('<div class="%s">%s</div>', $level, $content);
		return $this->addElement( new \Library\Form_Layout($content) );
	}
	
	public function getOutput() {
		$output = '';

		$output .= '<form id="'.$this->id.'" method="post" action="'.$this->action.'">';
			$output .= '<table class="smallIntBorder fullWidth" cellspacing="0">';

				foreach ($this->formElements as $element) {
					$output .= $element->getOutput();
				} 

				$output .= '<tr>';
					$output .= '<td>';
						$output .= '<span class="emphasis small">* '.__('denotes a required field').'</span>';
					$output .= '</td>';

					$output .= '<td class="right">';
						foreach ($this->hiddenValues as $name => $value) {
							$output .= '<input name="'.$name.'" value="'.$value.'" type="hidden">';
						} 
						$output .= '<input type="submit" value="'.__('Submit').'">';
					$output .= '</td>';

				$output .= '</tr>';
			$output .= '</table>';

			// Output the validation code, aggregated 
			$output .= '<script type="text/javascript">'."\n";

				foreach ($this->formElements as $element) {
					$output .= $element->getValidation();
				} 

			$output .= '</script>';
		$output .= '</form>';

		return $output;
	}

	public function output() {
		echo $this->getOutput();
	}
}