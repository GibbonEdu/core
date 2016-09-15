<?php
/*
 * Record Interface
 *

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
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage                     Record
 */
/**
 * Namespace
 */
namespace Gibbon\Record ;

use Gibbon\core\view ;

/**
 * Record Manager Interface
 *
 * @version	24th August 2016
 * @since	30th April 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage                     Record
 */
interface recordInterface
{
	/**
	 * Construct
	 * @version	24th August 2016
	 * @since	30th April 2016
	 * @param	Gibbon\core\view	$view
	 * @param	integer		$id	RecordID
	 * @return	void
	 */
	public function __construct( view $view, $id = 0 );

	/**
	 * Default Record
	 *
	 * @version	30th April 2016
	 * @since	30th April 2016
	 * @return	void
	 */
	public function defaultRecord();

	/**
	 * find
	 *
	 * @version	30th April 2016
	 * @since	30th April 2016
	 * @param	integer		Identifier
	 * @return	void
	 */
	public function find($id);

	/**
	 * inject Post
	 *
	 * @version	9th May 2016
	 * @since	30th April 2016
	 * @return	object		Record
	 */
	public function injectPost($data = null);

	/**
	 * inject Post
	 *
	 * @version	30th April 2016
	 * @since	30th April 2016
	 * @return	object		Record
	 */
	public function uniqueTest();

	/**
	 * write Record
	 *
	 * @version	30th April 2016
	 * @since	30th April 2016
	 * @return	boolean		
	 */
	public function writeRecord();

	/**
	 * delete Record
	 *
	 * @version	30th April 2016
	 * @since	30th April 2016
	 * @param	integer		Identifier
	 * @return	boolean		
	 */
	public function deleteRecord($id);

	/**
	 * can Delete
	 *
	 * @version	25th May 2016
	 * @since	25th May 2016
	 * @return	boolean		
	 */
	public function canDelete();
}

