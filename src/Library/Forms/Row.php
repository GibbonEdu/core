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
 * Row
 *
 * @version	v14
 * @since	v14
 */
class Row {

	protected $id;
	protected $class;

	protected $formElements = array();

	public function __construct($id = '') {
		$this->id = $id;
		$this->class = '';
	}

	public function addElement($id, FormElementInterface $element) {
		if (empty($id)) $id = 'element-'.count($this->formElements);

		$this->formElements[$id] = $element;
		return $element;
	}

	public function getElement($id = '') {
		if (empty($this->formElements) || count($this->formElements) == 1) return null;
		return (isset($this->formElements[$id]))? $this->formElements[$id] : end($this->formElements);
	}

	public function addContent($content) {
		return $this->addElement( '', new \Library\Forms\Layout\Content($content) );
	}

	public function addLabel($for, $label) {
		return $this->addElement( '', new \Library\Forms\Layout\Label($this, $for, $label) );
	}

	public function addTextField($name) {
		return $this->addElement( $name, new \Library\Forms\Input\TextField($name) );
	}

	public function addTextArea($name) {
		return $this->addElement( $name, new \Library\Forms\Input\TextArea($name) );
	}

	public function addSelect($name) {
		return $this->addElement( $name, new \Library\Forms\Input\Select($name) );
	}

	public function addSelectSchoolYear($name, \Gibbon\sqlConnection $pdo) {
		$sql = 'SELECT gibbonSchoolYearID as `value`, name FROM gibbonSchoolYear ORDER BY sequenceNumber';
		$results = $pdo->executeQuery(array(), $sql);

		return $this->addSelect($name)->fromQuery($results)->placeholder('Please select...');
	}

	public function addSelectLanguage($name, \Gibbon\sqlConnection $pdo) {
		$sql = 'SELECT name as `value`, name FROM gibbonLanguage ORDER BY name';
		$results = $pdo->executeQuery(array(), $sql);

		return $this->addSelect($name)->fromQuery($results)->placeholder('Please select...');
	}

	public function addYesNo($name) {
		return $this->addSelect($name)->fromArray( array( 'Y' => 'Yes', 'N' => 'No') );
	}

	public function addHeading($label) {
		$this->setClass('break');
		$content = sprintf('<h3>%s</h3>', $label);
		return $this->addContent($content);
	}

	public function addSubheading($label) {
		$content = sprintf('<h4>%s</h4>', $label);
		return $this->addContent($content);
	}

	public function addAlert($content, $level = 'warning') {
		$content = sprintf('<div class="%s">%s</div>', $level, $content);
		return $this->addContent($content);
	}

	public function addSubmit($label = 'Submit') {
		$content = sprintf('<input type="submit" value="%s">', $label);
		return $this->addContent($content)->setClass('right');
	}

	public function addButton($label = 'Button', $onClick = '') {
		$content = sprintf('<input type="button" value="%s" onClick="%s">', $label, $onClick);
		return $this->addContent($content)->setClass('right');
	}

	public function setClass($value = '') {
		$this->class = $value;
		return $this;
	}

	public function getID() {
		return $this->id;
	}

	public function getClass() {
		return $this->class;
	}

	public function getElements() {
		return $this->formElements;
	}

	public function getElementCount() {
		return count($this->formElements);
	}

	public function isLastElement($element) {
		return (end($this->formElements) == $element);
	}
}