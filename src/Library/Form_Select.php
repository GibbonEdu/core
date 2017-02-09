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
class Form_Select extends Form_Element {

	protected $options = array();

	protected $selected = null;
	protected $placeholder;

	public function fromString($value) {
		$pieces = explode(',', $value);

		foreach ($pieces as $piece) {
			$piece = trim($piece);

			$this->options[$piece] = $piece;
		}

		return $this;
	}

	public function fromArray($value) {
		$this->options = $value;

		return $this;
	}

	public function fromQuery($results) {
		
		while ($row = $results->fetch()) {
			if (!isset($row['value']) || !isset($row['name'])) continue;

			$this->options[$row['value']] = $row['name'];
		}

		return $this;
	}

	public function selected($value) {
		$this->selected = $value;

		return $this;
	}

	public function placeholder($value) {
		$this->placeholder = $value;

		$this->addValidation('Validate.Exclusion', 'within: [\''.$value.'\'], failureMessage: "'.__('Select something!').'"');

		return $this;
	}

	protected function getElement() {
		$output = '';

		$output .= '<select id="'.$this->name.'" name="'.$this->name.'" class="'.$this->class.'">';

		if (!empty($this->placeholder)) {
			$output .= '<option value="'.$this->placeholder.'">'.__($this->placeholder).'</option>';
		}

		foreach ($this->options as $value => $label) {
			if (is_array($label)) {
				$output .= '<optgroup label="'.$value.'">';
				foreach ($label as $subvalue => $sublabel) {
					$selected = ($this->selected == $subvalue)? 'selected' : '';
					$output .= '<option value="'.$subvalue.'" '.$selected.'>'.__($sublabel).'</option>';
				}
				$output .= '</optgroup>';
			} else {
				$selected = ($this->selected == $value)? 'selected' : '';
				$output .= '<option value="'.$value.'" '.$selected.'>'.__($label).'</option>';
			}
		}

		$output .= '</select>';

		return $output;
	}
}