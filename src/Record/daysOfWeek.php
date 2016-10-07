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
 * Days of Week Record
 *
 * @version	6th October 2016
 * @since	4th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage  Record
 */
class daysOfWeek extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonDaysOfWeek';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonDaysOfWeekID';
	
	/**
	 * Unique Test
	 *
	 * @version	29th September 2016
	 * @since	4th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', $this->table) ;
		$required = array('schoolDay', 'sequenceNumber');
		foreach ($required as $name) 
			if (! isset($this->record->$name))
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', $this->table, array($name)) ;
		$days = array(	'Mon'=>'Monday',
						'Tue'=>'Tuesday',
						'Wed'=>'Wednesday',
						'Thu'=>'Thursday',
						'Fri'=>'Friday',
						'Sat'=>'Saturday',
						'Sun'=>'Sunday'
					);
		if (! in_array($this->record->name, $days)) return $this->uniqueFailed('The day name given does not exist.', 'Debug', $this->table, (array)$this->returnRecord()) ;
		if (! array_key_exists($this->record->nameShort, $days)) return $this->uniqueFailed('The day code given does not exist.', 'Debug', $this->table, (array)$this->returnRecord()) ;
		return true ;
	}

	/**
	 * Get Record
	 *
	 * @version	4th May 2016
	 * @since	4th May 2016
	 * @poaram	integer		$id Identifier
	 * @return	void
	 */
	public function find($id)
	{
		$this->record = parent::find($id);
	}

	/**
	 * get School Days
	 *
	 * @version	6th October 2016
	 * @since	19th May 2016
	 * @param	boolean		$schoolDays
	 * @return	array	School OPen/Closed in the Week.
	 */
	public function getSchoolDays($schoolDays = false)
	{
		//Check which days are school days
		$days = array();
		$rowDays = $this->findAll("SELECT *
			FROM `gibbonDaysOfWeek` 
			ORDER BY `sequenceNumber`", array(), '_');
		foreach($rowDays as $row) {
			if ($schoolDays && $row->getField('schoolDay') == 'Y')
				$days[$row->getField('nameShort')] = (array) $row->returnRecord();
			elseif (! $schoolDays)
				$days[$row->getField('nameShort')] = (array) $row->returnRecord();
		}
		return $days ;
	}

	/**
	 * Inject Post
	 *
	 * @version	20th May 2016
	 * @since	20th May 2016
	 * @param	array		$data  Defaults to $_POST
	 * @return	boolean
	 */
	public function injectPost($data = null)
	{
		$ok = parent::injectPost();
		if ($this->record->schoolDay === 'N')
		{
			$this->record->schoolOpen = null;
            $this->record->schoolStart = null;
            $this->record->schoolEnd = null;
            $this->record->schoolClose = null;
		}
		return $ok ;
	}

	/**
	 * can Delete
	 *
	 * @version	25th May 2016
	 * @since	25th May 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		return true;
	}

	/**
	 * find All Days
	 *
	 * @version	29th September 2016
	 * @since	29th September 2016
	 * @return	array	Gibbon\Record\daysOfWeek
	 */
	public function findAllDays()
	{
		$weekStart = $this->view->config->getSettingByScope('System', 'firstDayOfTheWeek');
		
		$x = $this->findOneBy(array('name'=>'Sunday'));
		if ($weekStart == 'Sunday' && $this->getField('sequenceNumber') >= 7)
		{
			$this->setField('sequenceNumber', 0);
			$this->writeRecord(array('sequenceNumber'));
		}
		elseif ($weekStart == 'Monday' && $this->getField('sequenceNumber') == 0)
		{
			$this->setField('sequenceNumber', 7);
			$this->writeRecord(array('sequenceNumber'));
		}
		
		return $this->findAll("SELECT * 
			FROM `gibbonDaysOfWeek` 
			ORDER BY `sequenceNumber`", array(), NULL, 'nameShort');
	}
}
