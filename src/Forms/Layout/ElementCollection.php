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

namespace Gibbon\Forms\Layout;

use Gibbon\Forms\RowDependancyInterface;
use Gibbon\Forms\OutputableInterface;
use Gibbon\Forms;

/**
 * A collection of other elements bundled into one
 *
 * @version v14
 * @since   v14
 */
class ElementCollection implements RowDependancyInterface, OutputableInterface, \Gibbon\Forms\BasicAttributesInterface
{

	protected $content;
	protected $row;
	protected $factory;
	protected $id;

	public function __construct($name, $contentArr = array())
	{
		$this->id = $name;
		$this->content = array();
	}

	public function getID()
	{
		return $this->id;
	}

	public function getClass()
	{
		return 'ElementCollection';
	}

	public function setFactory($factory)
	{
		$this->factory = $factory;
	}

	public function setRow($row)
	{
		$this->row = $row;
	}

	public function setContent($value)
	{
		$this->content = $value;
		return $this;
	}

	public function addElement($element)
	{
		array_push($this->content,$element);
		return $this;
	}

	public function append($element)
	{
		array_push($this->content,$element);
		return $this;
	}

	public function prepend($element)
	{
		array_push($this->content,$element);
		return $this;
	}

	public function getOutput()
	{
		$output = "";
		foreach($this->getElement() as $element)
		{
			$output .= $element->getOutput();
		}
		return $output;
	}

	protected function getElement()
	{
		return $this->content;
	}

	public function addButton($label,$onclick)
	{
		$this->addElement($this->factory->createButton($label,$onclick));
	}

}
