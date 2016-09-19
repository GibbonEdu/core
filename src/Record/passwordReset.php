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

use Gibbon\core\trans ;

/**
 * Password Reset Record
 *
 * @version	19th September 2016
 * @since	18th June 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class passwordReset extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonPasswordReset';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonPasswordResetID';
	
	/**
	 * @var	object	$person	Gibbon\Record\person
	 */
	protected $person ;
	
	/**
	 * Unique Test
	 *
	 * @version	19th September 2016
	 * @since	18th June 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', 'Password Reset') ;
		$sql = 'SELECT * FROM `gibbonPasswordReset` WHERE `gibbonPersonID` = :personID';
		$data = array('personID' => $this->getField('gibbonPersonID'));
		$x = $this->findAll($sql, $data);
		if ($this->getSuccess() && count($x) == 0)
			return true ;
		if (! $this->getSuccess())
			return $this->uniqueFailed('Failed to load the record.', 'Debug', 'Password Reset', (array)$this->record) ;
		$pr = reset($x);
		$d = new \DateTime(date('Y-m-d H:i:s', strtotime('now')), new \DateTimeZone('UTC'));
		$e = clone $d;
		$d->setTimeStamp($pr->getField('requestTime'));
		$df = $e->diff($d, true);
		if ($df->days >= 1)
		{
			$ok = $pr->deleteRecord($pr->getField('gibbonPasswordResetID'));
			if ($ok)
				return true;
			else
				return $this->uniqueFailed('Failed to delete an old password reset request.', 'Debug', 'Password Reset', (array)$this->record) ;
		}
		return $this->uniqueFailed('A valid password reset for this use already exists.', 'Debug', 'Password Reset', (array)$this->record) ;
	}

	/**
	 * can Delete
	 *
	 * @version	18th June 2016
	 * @since	18th June 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		return true;
	}

	/**
	 * generate Token
	 *
	 * @version	18th June 2016
	 * @since	18th June 2016
	 * @param	integer		$personID	
	 * @param	integer		$length	
	 * @return	string		
	 */
	public function generateToken($personID, $length = 32)
	{
		$token = $this->view->getSecurity()->generateToken($length);
		$this->defaultRecord();
		$this->setField('gibbonPersonID', $personID);
		$this->setField('token', $token);
		if ($this->uniqueTest()) 
		{
			$this->defaultRecord();
			$this->setField('gibbonPersonID', $personID);
			$this->setField('token', $token);
			if (! $this->writeRecord())
				dump($this, true);
			return $token;
		}
		return null ;
	}

	/**
	 * Default Record
	 *
	 * @version	4th May 2016
	 * @since	4th May 2016
	 * @return	stdClass	Record
	 */
	public function defaultRecord()
	{
  		parent::defaultRecord();
		$this->record->requestTime = strtotime('now');
		return $this->record ;
	}

	/**
	 * get Person
	 *
	 * @version	19th June 2016
	 * @since	19th June 2016
	 * @return	object	
	 */
	public function getPerson()
	{
		if ($this->person instanceof person && $this->record->gibbonPersonID === $this->person->getField('gibbonPersonID'))
			return $this->person;
		if (isset($this->record->gibbonPersonID) && intval($this->record->gibbonPersonID) > 0)
			$this->person = new person($this->view, $this->record->gibbonPersonID);
		else 
			$this->person = new person($this->view);
		return $this->person;
	}

	/**
	 * valid Token
	 *
	 * @version	19th June 2016
	 * @since	19th June 2016
	 * @return	boolean	
	 */
	public function validToken()
	{
		if (! isset($_GET['token']))
			return false ;
		$args = array("token" => FILTER_SANITIZE_STRING);

		$post = filter_input_array(INPUT_GET, $args);
		$token = $post['token'];
		$this->findOneBy(array('token' => $token));
		if (! isset($this->record) || ! $this->record instanceof \stdClass || $this->getField('token') !== $token)
			return false ; 
		$d = new \DateTime(date('Y-m-d H:i:s', strtotime('now')), new \DateTimeZone('UTC'));
		$e = clone $d;
		$d->setTimeStamp($this->getField('requestTime'));
		$df = $e->diff($d, true);
		if ($df->days >= 1)
		{
			$pr->deleteRecord($pr->getField('gibbonPasswordResetID'));
			return false;
		}
		return true ;
	}

	/**
	 * get Reset Status
	 *
	 * @version	20th July 2016
	 * @since	20th July 2016
	 * @return	boolean	
	 */
	public function getResetStatus($personID)
	{
		$this->findOneBy(array('gibbonPersonID' => $personID));
		if ($this->rowCount() == 1)
		{
			if ($this->getField('requestTime') > strtotime('now') - 86400)
				return false ;
			else
				$this->deleteRecord($this->getField('gibbonPasswordResetID'));
		}
		return true;
	}
}
