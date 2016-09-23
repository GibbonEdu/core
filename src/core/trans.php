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
namespace Gibbon\core;

use Gibbon\Record\stringReplacement ;

/**
 * Translation Class
 *
 * Translation is read from a master Yaml file at ./i18n/gibbon.yml<br />
 * if a module is called, then a second file (if available) is loaded from ./modules/{moduleName}/i18n/{lc_code}.yml
 * @version	21st September 2016
 * @since	16th April 2016
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage	Core
 */
class trans
{
	/**
	 * @var	Translation Matrix
	 */
	protected $matrix = array();

	/**
	 * @var	Translation Source
	 */
	protected $source;

	/**
	 * Get and store custom string replacements in session
	 *
	 * (Moved from Functions)
	 * @version 18th September 2016
	 * @since	Old
	 * @param	Gibbon\sqlConnection
	 * @return	void
	 */
	public function setStringReplacementList(view $view)
	{
		if ($view->session->isEmpty('stringReplacement') || $view->session->get('refreshCache'))
		{
			$view->session->set('stringReplacement', array()) ;
			$sObj = new stringReplacement($view);
			$result = $sObj->findAll('SELECT * 
				FROM `gibbonString` 
				ORDER BY `priority` DESC, `original` ASC');
			if (count($result) > 0)
				foreach($result as $q=>$w)
					$view->session->push('stringReplacement', $w->returnRecord()) ;
			else
				$view->session->set('stringReplacement', false) ;
		}
	}

	/**
	 * Custom translation function to allow custom string replacement
	 *
	 * This is now a replacement for the getText standard PHP translation class.  It uses standard yml files for string translation.  The method allows for plural, numerical and gender replacement as well as sub-string injection after translation.  An Example is: <br />
return: <br />
    error: <br />
        0: Your request failed because you do not have access to this action. <br />
        1: Your request failed because your inputs were invalid. <br />
        2: Your request failed due to a database error. <br />
    warning: <br />
        0: Your optional extra data failed to save. <br />
        1: Your request was successful, but some data was not properly saved. <br />
        2: Your request was successful, but some data was not properly deleted. <br />
    success: <br />
        0: Your request was completed successfully.	  <br />
using the three injected variables, you can select any of these translation strings using a number of different methods:<br />
A: __('return.error', array(), 0) will return error message 0<br />
B: __('return.error.1') will return error message 1<br />
C: __('return.error.4') will return 'return.error.4' as the translation was not found.<br />
D: __('return.error', array(), 4) will return error message 2 as Choice if not found will pop the last value from the array.<br />
Another Example:<br />
gender:<br />
    his:<br />
		F: her<br />
		M: his<br />
A: __('gender.his', array(), 'F') will return 'her'<br />
B: __('gender.his', array(), 'X') will return 'his'<br />
Another Example:<br />
plural:<br />
    apples:<br />
		0: I have no apples.<br />
		1: I have an apple.<br />
		2: I have %1$s apples.<br />
A: __('plural.apples', array(0), 0) will return 'I have no apples.'<br />
B: __('plural.apples', array(1), 1) will return 'I have an apple.'<br />
C: __('plural.apples', array(3), 3) will return 'I have 3 apples.'<br />
	 * @version 21st September 2016
	 * @since	Moved from Functions
	 * @param	string/array		$text	String to be translated.
	 * @param	array		$options	sprintf Options to add after translation of text.
	 * @param	mixed		$choice		
	 * @return	string		Translated String
	 */
	public function __($text, $options = array(), $choice = NULL)
	{
		if (is_array($text)) return $this->__($text[0], $text[1]);
		$text = trim($text);
		$text = trim($text, '"');
		$text = trim($text, "'");
		$this->source = $text ;

		$text = getText($this->source) ;
		
		if (! empty($options)) {
			try {
				$text = vsprintf($text, $options);
			} catch(\Exception $e)
			{
				throw new \Exception('Incorrect translation parameter count: '.$text.': '. implode(', ', $options), 30000 + __LINE__);
			}
		}
		
		$session = new session();
		$replacements = is_array($session->get('stringReplacement')) ? $session->get('stringReplacement') : array() ;
		if (empty($replacements)) return $text ;

		foreach ($replacements AS $replacement) {
			if ($replacement->mode == "Partial") { //Partial match
				if ($replacement->caseSensitive == "Y") {
					if (mb_strpos($text, $replacement->original) !== false) {
						$text = str_replace($replacement->original, $replacement->replacement, $text) ;
					}
				}
				else {
					if (mb_stripos($text, $replacement->original) !== false) {
						$text = str_ireplace($replacement->original, $replacement->replacement, $text) ;
					}
				}
			}
			else { //Whole match
				if ($replacement->caseSensitive == "Y") {
					if ($replacement->original == $text) {
						$text = $replacement->replacement ;
					}
				}
				else {
					if (mb_strtolower($replacement->original) == mb_strtolower($text)) {
						$text = $replacement->replacement ;
					}
				}
			}

		}

		return $text ;
	}
	
	/**
	 * Translation Construct
	 *
	 * en_GB is the default language..  It is always loaded first, then the language set of the system/user.<br />
	 * module lanaguage sets are loaded on top of the default sets in the same order.
	 * 
	 * @version	23rd September 2016
	 * @since	23rd September 2016
	 * @return	void
	 */
	public function __construct()
	{
		//Set up for i18n via gettext
		$session = new session();
		putenv('LC_ALL='.$session->get('i18n.code', 'en_GB'));
		setlocale(LC_ALL, $session->get('i18n.code', 'en_GB'));
		bindtextdomain('gibbon', './i18n');
		textdomain('gibbon');
		bind_textdomain_codeset('gibbon', 'UTF-8');
	}
}
