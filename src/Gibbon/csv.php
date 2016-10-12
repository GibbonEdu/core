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
 * CSV Generator
 *
 * @version	19th April 2016
 * @since	14th April 2016
 * @author	Craig Rayner
 */
class csv
{
	
	/**
	 * string
	 */
	static private $title;
	
	/**
	 * Generate
	 *
	 * direct output of csv to browser.
	 *
	 * @version	19th April 2016
	 * @since	14th April 2016
	 * @param	Object	Gibbon\sqlConnection
	 * @param	string	Title
	 * @param	string	Header (Must be formated in csv)
	 * @return	void
	 */
	static public function generate( sqlConnection $pdo, $title, $header = NULL)
	{
		self::$title = self::testTitle($title);
		$start = true;
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private", false);
		header("Content-Type: text/csv");
		header('Content-Disposition: attachment; filename="'.self::$title.'";' );
		while ($row = $pdo->getResult()->fetch()) 
		{
			if ($start)
			{
				$start = false;
				if ($header === NULL)
				{
					$header = '';
					foreach ($row as $colName=>$value)
						$header .= self::encodeCSVField($colName).',';
					$header = rtrim($header, ",") . "\n";
					echo $header;
				}
				else
					echo $header;
			}
			$line = '';
			foreach($row as $value)
				$line .= self::encodeCSVField($value).',';
			$line = rtrim($line, ",") . "\n";
			echo $line;
		}
	}
	
	/**
	 * Test Title
	 *
	 * @version	14th April 2016
	 * @since	14th April 2016
	 * @param	string	Title
	 * @return	string	Title
	 */
	static private function testTitle($title)
	{
		$x = explode('.',$title);
		if (count($x) >= 2)
			array_pop($x);
		$x[] = 'csv';
		return implode('.', $x);
	}

	
	/**
	 * encode CSV Field
	 *
	 * @version	14th April 2016
	 * @since	14th April 2016
	 * @param	string	CSV Data
	 * @return	string	CSV Data
	 */
	static private function encodeCSVField($string) 
	{
		if(strpos($string, ',') !== false || strpos($string, '"') !== false || strpos($string, "\n") !== false) 
			$string = '"' . str_replace('"', '""', $string) . '"';
		return $string;
	}
}