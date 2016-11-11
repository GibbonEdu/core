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
	public function __construct( sqlConnection $pdo, session $session )
	{
		$this->pdo = $pdo;
		$this->session = $session;
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
		$stringReplacements = array();
		
		if ($this->pdo->getConnection() != null) {
			$data = array();
			$sql="SELECT original, replacement, mode, caseSensitive FROM gibbonString ORDER BY priority DESC, original" ;
			$result = $this->pdo->executeQuery($data, $sql);

			if ($result->rowCount()>0) {
				$stringReplacements = $result->fetchAll();
			}
		}

		$this->session->set('stringReplacement', $stringReplacements );
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
	public function __($text, $guid = true)
	{
		$replacements = $this->session->get('stringReplacement', null);

		// Do this once per session, only if the value doesn't exist
		if ($replacements === null) {
			$this->setStringReplacementList();
		}

		$text=_($text) ;

		if (isset($replacements) && is_array($replacements)) {

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

		return $text ;
	}
}
?>
