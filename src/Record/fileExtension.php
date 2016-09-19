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
namespace Gibbon\Record ;

/**
 * File Extension Record
 *
 * @version	9th September 2016
 * @since	4th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class fileExtension extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonFileExtension';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonFileExtensionID';
	
	/**
	 * Unique Test
	 *
	 * @version	4th May 2016
	 * @since	4th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (	empty($this->record->extension)
				|| empty($this->record->name)
				|| empty($this->record->type)) 
			return false ;
		$data=array("extension" => $this->record->extension, "gibbonFileExtensionID" => $this->record->gibbonFileExtensionID); 
		$sql="SELECT * FROM `gibbonFileExtension` WHERE (`extension`=:extension) AND NOT `gibbonFileExtensionID`=:gibbonFileExtensionID" ;
		$this->executeQuery($data, $sql);
		if ($this->rowCount() > 0) return false ;
		return true ;
	}

	/**
	 * Inject Pst
	 *
	 * @version	9th September 2016
	 * @since	9th September 2016
	 * @return	boolean
	 */
	public function injectPost($data = null)
	{
		$data = $_POST;
		if (! empty($data['mimeType']))
		{
			$y = explode(',', $data['mimeType']);
			$data['mimeType'] = array();
			foreach($y as $w)
				if (! empty(trim($w)))
					$data['mimeType'][] = trim($w);
			$data['mimeType'] = implode(',', $data['mimeType']);
		}
		return parent::injectPost($data);
	}

	/**
	 * can Delete
	 *
	 * @version	7th July 2016
	 * @since	7th July 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		return true;
	}

	/**
	 * find mimeType
	 *
     * @version	2nd August 2016
	 * @since	2nd August 2016
	 * @param	array		$fileExtn
	 * @return	array
	 */
	public function findMimeType($fileExtns)
	{
		if (! is_array($fileExtns))
			return array();
		$mimeType = array();
		foreach ($fileExtns as $extn)
		{	
			$this->findOneBy(array('extension' => $extn));
			if ($this->getSuccess())
				$mimeType = array_merge($mimeType, explode(',', $this->getField('mimeType')));
		}
		return $mimeType;
	}

	/**
	 * get Extension List
	 *
     * @version	20th August 2016
	 * @since	20th August 2016
	 * @param	array		$fileExtn
	 * @return	array
	 */
	public function getExtensionList()
	{
		$w = $this->findAll('SELECT `extension` FROM `gibbonFileExtension` ORDER BY `extension`', array(), null, 'extension');	
		$extensions = array();
		foreach($w as $x);
			$extension[] = $x->getField('extension');	
		return $extensions;
	}
}
