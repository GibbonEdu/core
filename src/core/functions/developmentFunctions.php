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
namespace Gibbon\core\functions ;

/**
 * String Functions
 *
 * @version	18th September 2016
 * @since	18th September 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Trait
 */
trait developmentFunctions
{
	/**
	 * Print an Object Alias (Dump)
	 *
	 * @version	16th February 2015
	 * @since	OLD
	 * @param	mixed 		$object		The object to be printed
	 * @param	boolean 	$stop		Stop execution after printing object.
	 * @param	boolean 	$full		Full print the Call Trace Stack
	 * @return	void
	 */
	public function dump($object, $stop = false, $full = false) 
	{
		$caller = debug_backtrace(false);
		echo "<pre>\n";
		echo $caller[0]['line'].': '.$caller[0]['file'];
		echo "\n</pre>\n";
		echo "<pre>\n";
		print_r($object);
		if ($full) 
			print_r($caller);
		echo "\n</pre>\n";
		if ($stop) 
			die();
		flush();
		return ;
	}
	
	/**
	 * File an Object
	 *
	 * @version 10th November 2014
	 * @since OLD
	 * @param mixed The object to be printed
	 * @param string Name of File
	 * @return void
	 */
	public function fileAnObject($object, $name = null)
	{
		
		$logpath = GIBBON_CONFIG;
		if ($name === null)
			$fn = substr(md5(print_r($object, true)), 0, 12).'.log';
		else
			$fn = $name . '.log';
		$caller = debug_backtrace( false );
		$data = $caller[0]['line'].': '.$caller[0]['file']."\n";
		$data .= print_r($object, true);
		$x = '';
		foreach ($caller as $w) {
			$x =  $w['line'].': '.$w['file'].' '.$w['function']."\n". $x;
		}
		$data .= "\n".$x;
		file_put_contents($logpath . $fn, $data);
	//	die(__FILE__.': '.__LINE__);
		return ;
	}



	// Script end
	private function rutime($ru, $rus, $index)
	{
		return ($ru["ru_$index.tv_sec"]*1000 + intval($ru["ru_$index.tv_usec"]/1000))
		 -  ($rus["ru_$index.tv_sec"]*1000 + intval($rus["ru_$index.tv_usec"]/1000));
	}

	public function stop($die = false)
	{
		$ru = getrusage();
		echo "<br />This process used " . $this->rutime($ru, $this->session->get('rustart'), "utime") .
			" ms for its computations\n";
		echo "It spent " . $this->rutime($ru, $this->session->get('rustart'), "stime") .
			" ms in system calls\n";
		if ($die)
			die();
	}
}