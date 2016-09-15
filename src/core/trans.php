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

use Symfony\Component\Yaml\Yaml ;
use Gibbon\Record\stringReplacement ;

/**
 * Translation Class
 *
 * Translation is read from a master Yaml file at ./i18n/gibbon.yml<br />
 * if a module is called, then a second file (if available) is loaded from ./modules/{moduleName}/i18n/{lc_code}.yml
 * @version	5th September 2016
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
	static $matrix = array();

	/**
	 * @var	Translation Source
	 */
	static $source;

	/**
	 * Get and store custom string replacements in session
	 *
	 * (Moved from Functions)
	 * @version 5th August 2016
	 * @since	Old
	 * @param	Gibbon\sqlConnection
	 * @return	void
	 */
	static public function setStringReplacementList(view $view)
	{
		if ($view->session->isEmpty('stringReplacement') || $view->session->get('refreshCache'))
		{
			$view->session->set('stringReplacement', array()) ;
			$sObj = new stringReplacement($view);
			$result = $sObj->findAll('SELECT * 
				FROM `gibbonStringReplacement` 
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
	 * @version 1st July 2016
	 * @since	Moved from Functions
	 * @param	string/array		$text	String to be translated.
	 * @param	array		$options	sprintf Options to add after translation of text.
	 * @param	mixed		$choice		
	 * @return	string		Translated String
	 */
	static public function __($text, $options = array(), $choice = NULL)
	{
		self::$matrix = self::loadMatrix();
		if (is_array($text)) return self::__($text[0], $text[1]);
		$text = trim($text);
		$text = trim($text, '"');
		$text = trim($text, "'");
		self::$source = $text ;

		$text = self::getText($text) ;

		if (is_array($text) and isset($text[$choice]))
			$text = $text[$choice];
		elseif (is_array($text))
			$text = array_pop($text);
		
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
	 * Load Language Matrix
	 *
	 * en_GB is the default language..  It is always loaded first, then the language set of the system/user.<br />
	 * module lanaguage sets are loaded on top of the default sets in the same order.
	 * 
	 * @version	27th June 2016
	 * @since	18th May 2016
	 * @return	array
	 */
	private static function loadMatrix()
	{
		if (count(self::$matrix) == 0)
		{
			$session = new session();
			$i18n = $session->get('i18n.code');
			
			//Load Default en_GB
			$file = GIBBON_ROOT.'/i18n/en_GB/gibbon.yml';
			if (file_exists($file)) {
				self::$matrix = Yaml::parse(file_get_contents($file));
				if ( empty(self::$matrix)) self::$matrix = array();
			}

			// Load System Language
			$file = GIBBON_ROOT.'/i18n/'.$i18n.'/gibbon.yml';
			if ($i18n !== 'en_GB' && file_exists($file)) {
				$mTrans = Yaml::parse(file_get_contents($file));
				if (empty($mTrans)) $mTrans = array();
				self::$matrix = array_merge(self::$matrix, $mTrans);
			}

			$module = $session->get('module');
			
			// Load Module Default Lanaguae
			$file = GIBBON_ROOT.'/modules/'.$module.'/i18n/en_GB.yml';
			if (file_exists($file)) {
				$mTrans = Yaml::parse(file_get_contents($file));
				if (empty($mTrans)) $mTrans = array();
				self::$matrix = array_merge(self::$matrix, $mTrans);
			}
			
			// Load Module System Lanaguae
			$file = GIBBON_ROOT.'/modules/'.$module.'/i18n/'.$i18n.'.yml';
			if ($i18n !== 'en_GB' && file_exists($file)) {
				$mTrans = Yaml::parse(file_get_contents($file));
				if (empty($mTrans)) $mTrans = array();
				self::$matrix = array_merge(self::$matrix, $mTrans);
			}
			
		}
		return self::$matrix ;
	}
	
	/**
	 * get Text
	 *
	 * @version	18th May 2016
	 * @since	18th May 2016
	 * @param	string		$text
	 * @return	array|string
	 */
	private static function getText($text)
	{
		
		self::$source = $text;
		if (isset(self::$matrix[$text]) && is_string(self::$matrix[$text]))
			return self::$matrix[$text] ;
		$period = strpos($text, '.') ;
		if ($period === false)
		{
			$key = $text;
		}
		else
		{
			$key = substr($text, 0, $period) ;
			$sub = trim(substr($text, $period + 1));
		}
		if (isset(self::$matrix[$key]))
		{
			if (is_array(self::$matrix[$key]))
			{
				if (! isset($sub))
					return self::$matrix[$key];
				else
					return self::getSubText(self::$matrix[$key], $sub);
			}
		}
		// Not Found
		self::reportTranslationMissing();
		return self::$source ;
	}
	
	/**
	 * get Sub Text
	 *
	 * @version	18th May 2016
	 * @since	18th May 2016
	 * @param	array		$matrix
	 * @param	string		$text
	 * @return	array|string
	 */
	private static function getSubText($matrix, $text)
	{
		if (isset($matrix[$text]) && is_string($matrix[$text]))
			return $matrix[$text] ;
		$period = strpos($text, '.') ;
		$key = substr($text, 0, $period) ;
		if ( $period === false && isset($matrix[$text]))
		{
		return $matrix[$text];
		}
		if (isset($matrix[$key]))
		{
			if (is_array($matrix[$key]))
			{
				return self::getSubText($matrix[$key], substr($text, $period + 1));
			}
			elseif (is_string($matrix[$key]))
				return $matrix[$key] ;
		}
		// Not Found
		self::reportTranslationMissing();
		return self::$source ;
	}
	
	/**
	 * report Translation Missing
	 *
	 * @version	27th June 2016
	 * @since	18th May 2016
	 * @return	void
	 */
	private static function reportTranslationMissing()
	{
		$session = new session();
		if ($session->get('installType') !== 'Development')
			return ;

		$x = $session->get('i18n.missing');
		
		if (isset($x[self::$source]))
			return ;

		
		$x[self::$source] = self::$source ;
		
		$session->set('i18n.missing', $x);

		return ;

	}
	
	/**
	 * Write Translation Missing
	 *
	 * @version	5th September 2016
	 * @since	27th June 2016
	 * @return	void
	 */
	public static function writeTranslationMissing()
	{
		$session = new session();
		if ($session->get('installType') !== 'Development')
			return ;

		if ($session->isEmpty('i18n.missing'))
			return ;
		
		$x = $session->get('i18n.missing');
		if (! is_array($x))
		{
			$x = $session->clear('i18n.missing');
			return ;
		}
		
		if (file_exists(GIBBON_ROOT.'/i18n/en_GB/gibbon.yml'))
			$report = Yaml::parse(file_get_contents(GIBBON_ROOT.'/i18n/en_GB/gibbon.yml'));
		if (empty($report))
			$report = array();
		foreach($x as $source)
		{
			$report[$source] = $source ;
		}
		
		file_put_contents(GIBBON_ROOT.'/i18n/en_GB/gibbon.yml', Yaml::dump($report));
		
		$session->clear('i18n.missing');
		
		return ;
	}
}
