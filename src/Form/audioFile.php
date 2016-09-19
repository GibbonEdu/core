<?php
/**
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
/**
 */
namespace Gibbon\Form;

use Gibbon\core\view ;
use Gibbon\Record\fileExtension ;

/**
 * Ausio File Element
 *
 * @version	19th September 2016
 * @since	30th June 2016
 * @author	Craig Rayner
 * @package	Gibbon
 */
class audioFile extends element
{
	/**
	 * Constructor
	 *
	 * @version	19th September 2016
	 * @since	30th June 2016
	 * @param	string		$name
	 * @param	mixed		$value
	 * @param	Gibbon\core\view	$view
	 * @return 	void
	 */
	public function __construct($name = null, $value = null, view $view)
	{
		parent::__construct($name, $value, $view);
		if ($name !== NULL)
			$this->name = $name;
		if ($value !== NULL)
			$this->value = $this->view->htmlPrep($value);
		$this->element->name = 'audio';
		$this->id = '_'.$this->name ;
		$this->currentAttachment = '';
	}

	/**
	 * set Audio Validation
	 *
	 * @version	30th June 2016
	 * @since	30th June 2016
	 * @param	string		$message
	 * @param	string		$within
	 * @param	boolean		$partialMatch
	 * @param	boolean		$caseSensitive
	 * @return 	void
	 */
	public function setAudio(view $view, $message = 'Illegal Audio File!', $partialMatch = true, $caseSensitive = false )
	{
		$fe = new fileExtension($view);
		$x = $fe->findBy(array('type' => 'audio'));
		$within = '';
		while ($x = $fe->next()) {
			$within = $within . "'." . $x->extension . "',";
		}
		$this->getValidate()->Audio = true ;
		$this->getValidate()->Inclusion = true ;
		$this->validate->inclusionWithin = $within ;
		$this->validate->inclusionMessage = $message;
		$this->validate->inclusionPartialMatch = $partialMatch ? ', partialMatch: true' : ', partialMatch: false' ;
		$this->validate->inclusionCaseSensitive = $caseSensitive ? ', caseSensitive: true' : ', caseSensitive: false' ;
	}
}
