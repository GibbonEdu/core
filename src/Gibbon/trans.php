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

namespace Gibbon;

/**
 * Translation Class
 *
 * @version	16th April 2016
 * @since	16th April 2016
 * @author	Craig Rayner
 */
class trans
{
	/**
	 * Gibbon\sqlConnection
	 */
	private $pdo ;

	/**
	 * Gibbon\session
	 */
	private $session ;

	/**
	 * Construct
	 *
	 * @version 22nd June 2016
	 * @since	16th April 2016
	 * @return	void
	 */
	public function __construct()
	{
		$this->session = new session();
	}

	/**
	 * Get and store custom string replacements in session
	 *
	 * (Moved from Functions)
	 * @version 22nd June 2016
	 * @since	Old
	 * @return	void
	 */
	public function setStringReplacementList()
	{
		$this->pdo = new sqlConnection();
		$this->session->set('stringReplacement', array()) ;
		$sql="SELECT * FROM gibbonString ORDER BY priority DESC, original" ;
		$result = $this->pdo->executeQuery(array(), $sql);

		if ($result->rowCount()>0)
			$this->session->set('stringReplacement', $result->fetchAll()) ;
		else
			$this->session->set('stringReplacement', false) ;
	}
	/**
	 * Custom translation function to allow custom string replacement
	 *
	 * (Moved from Functions)
	 * @version 16th April 2016
	 * @since	Old
	 * @param	string	Text to Translate
	 * @param	boolean	Use guid.
	 * @return	string	Translated Text
	 */
//Custom translation function to allow custom string replacement
	public function __($text, $guid = true)
	{

		$replacements = $this->session->get('stringReplacement', $guid) !== NULL ? $this->session->get('stringReplacement', $guid) : array() ;

		$text=_($text) ;

		if (isset($replacements)) {
			if (is_array($replacements)) {
				foreach ($replacements AS $replacement) {
					if ($replacement["mode"]=="Partial") { //Partial match
						if ($replacement["caseSensitive"]=="Y") {
							if (strpos($text, $replacement["original"])!==FALSE) {
								$text=str_replace($replacement["original"], $replacement["replacement"], $text) ;
							}
						}
						else {
							if (stripos($text, $replacement["original"])!==FALSE) {
								$text=str_ireplace($replacement["original"], $replacement["replacement"], $text) ;
							}
						}
					}
					else { //Whole match
						if ($replacement["caseSensitive"]=="Y") {
							if ($replacement["original"]==$text) {
								$text=$replacement["replacement"] ;
							}
						}
						else {
							if (strtolower($replacement["original"])==strtolower($text)) {
								$text=$replacement["replacement"] ;
							}
						}
					}

				}
			}
		}

		return $text ;
	}
}
?>
