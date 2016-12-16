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
 * Localization & Internationalization Class
 *
 * Responsibilities:
 * 		- Locale
 * 		- Translation
 * 		- Timezones
 * 		- Languages
 * 		- Currency
 * 		- Character set
 * 		- RTL support
 *
 * @version	v13
 * @since	v13
 */
class Locale
{
	protected $i18n;

	protected $i18ncode;

	protected $session;

	protected $stringReplacements;

	/**
	 * Construct
	 */
	public function __construct( core $gibbon )
	{
		$this->session = $gibbon->session;

		// Setup the Internationalization code from session
		$this->i18n = $this->session->get('i18n');
		$this->setLocale($this->i18n['code']);
	}

	/**
	 * Set the current i18n code
	 * 
	 * @param   string $i18ncode
	 */
	public function setLocale($i18ncode)
	{
		// Cancel if there's no code set
		if (empty($i18ncode)) return;

		$this->i18ncode = $i18ncode;

		putenv('LC_ALL='.$this->i18ncode);
	    setlocale(LC_ALL, $this->i18ncode);
	}

	/**
	 * Get the current i18n code
	 * 
	 * @return  string
	 */
	public function getLocale() {
		return $this->i18ncode;
	}

	/**
	 * Set the default domain and load module domains
	 * 
	 * @param   Gibbon/sqlConnection  $pdo
	 */
	public function setTextDomain($pdo) {
		bindtextdomain('gibbon', $this->session->get('absolutePath').'/i18n');
	    bind_textdomain_codeset('gibbon', 'UTF-8');

	    if ($pdo->getConnection() != null) {
		    // Parse additional modules, adding domains for those

		    $data = array();
		    $sql = "SELECT name FROM gibbonModule WHERE active='Y' AND type='Additional'";
		    $result = $pdo->executeQuery($data, $sql);

		    if ($result->rowCount() > 0) {
		        while ($row = $result->fetch()) {
		            bindtextdomain($row['name'], $this->session->get('absolutePath').'/modules/'.$row['name'].'/i18n');
		        }
		    }
		}

	    // Set default domain
	    textdomain('gibbon');
	}

	/**
	 * Get and store custom string replacements in session
	 *
	 * @param   Gibbon/sqlConnection  $pdo
	 */
	public function setStringReplacementList($pdo, $forceRefresh = false)
	{	
		$stringReplacements = $this->session->get('stringReplacement', null);

		// Do this once per session, only if the value doesn't exist
		if ($forceRefresh || $stringReplacements === null) {
		
			$stringReplacements = array();

			if ($pdo->getConnection() != null) {
				$data = array();
				$sql="SELECT original, replacement, mode, caseSensitive FROM gibbonString ORDER BY priority DESC, original";

				$result = $pdo->executeQuery($data, $sql);

				if ($result->rowCount()>0) {
					$stringReplacements = $result->fetchAll();
				}
			}

			$this->session->set('stringReplacement', $stringReplacements );
		}

		$this->stringReplacements = $stringReplacements;
	}

	/**
	 * Custom translation function to allow custom string replacement
	 *
	 * @param	string	Text to Translate
	 * @param	boolean	Use guid.
	 * 
	 * @return	string	Translated Text
	 */
	public function translate($text, $domain = null)
    {
    	if ($text === '') return $text; 

        if (empty($domain))
            $text=_($text);

        else {
            $text = dgettext($domain, $text);

        }

		if (isset($this->stringReplacements) && is_array($this->stringReplacements)) {

			foreach ($this->stringReplacements AS $replacement) {
				if ($replacement["mode"]=="Partial") { //Partial match
					if ($replacement["caseSensitive"]=="Y") {
						if (strpos($text, $replacement["original"])!==FALSE) {
							$text=str_replace($replacement["original"], $replacement["replacement"], $text);

						}
					}
					else {
						if (stripos($text, $replacement["original"])!==FALSE) {
							$text=str_ireplace($replacement["original"], $replacement["replacement"], $text);

						}
					}
				}
				else { //Whole match
					if ($replacement["caseSensitive"]=="Y") {
						if ($replacement["original"]==$text) {
							$text=$replacement["replacement"];

						}
					}
					else {
						if (strtolower($replacement["original"])==strtolower($text)) {
							$text=$replacement["replacement"];

						}
					}
				}

			}
			
		}

		return $text;

	}
}