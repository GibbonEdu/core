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

namespace Gibbon\core;

use Gibbon\Record\recordInterface ;
use Gibbon\core\trans ;

/**
 * Pagination Manager
 *
 * @version	11th August 2016
 * @since	25th April 2016
 * @author	Craig Rayner
 * @package	Gibbon
 */
class pagination
{
	/**
	 * Gibbon\view
	 */
	private $view;

	/**
	 * @var	integer		Page Number
	 */
	private $page;

	/**
	 * @var	string		Where
	 */
	private $where;

	/**
	 * @var	string		select
	 */
	private $select;

	/**
	 * @var	string		Join
	 */
	private $join;

	/**
	 * @var	array		Data
	 */
	private $data;

	/**
	 * @var	array		Order
	 */
	private $order;

	/**
	 * @var	object		Record Interface Implementation
	 */
	private $record;

	/**
	 * @var	integer		Total Records to display.
	 */
	private $total;

	/**
	 * @var	integer		Pagination Counter
	 */
	private $pagination;

	/**
	 * @var	integer		Total Pages
	 */
	private $totalPages;

	/**
	 * @var	array		Results
	 */
	private $results;

	/**
	 * Constructor
	 *
	 * @version	29th June 2016
	 * @since	25th April 2016
	 * @param	Gibbon\view	$view
	 * @param	string		$where	Where string for paginination content
	 * @param	array		$data	Insert valkues for where
	 * @param	array		$order	
	 * @param	string		$join		Join	
	 * @param	string		$select		Select list
	 * @return	Gibbon\pagination
	 */
	public function __construct(view $view = NULL, $where, $data, $order, recordInterface $record, $join = '',  $select = '*')
	{
		if (! $view instanceof view)
			$view = new view();
		$this->view = $view ;
		$this->page = isset($_GET["page"]) ? intval($_GET["page"]) : 1 ;
		if ($this->page < 1) $this->page = 1 ;
		$this->where = $where ;
		$this->data = $data ;
		$this->order = $order ;
		$this->record = $record ;
		$this->select = $select ;
		$this->join = $join ;
		$this->pagination = $this->view->session->get('pagination');
		$this->generateDetails();
		return $this;
	}

	/**
	 * print Pagination
	 *
	 * @version	29th June 2016
	 * @since	25th April 2016
	 * @param	string		$position	Position
	 * @param	string		$get	Addition _GET Information
	 * @return	void
	 */
	public function printPagination($position, $get = null) 
	{
		if ($this->total <= $this->pagination)
			return ;
		
		$this->class="paginationTop" ;
		if ($position=="bottom") $this->class="paginationBottom" ;
		
		$this->getString = ! is_null($get) ? '&' . trim($get, '&') : '';
		$this->totalPages = ceil($this->total/$this->pagination) ;
		
		$this->view->render('default.pagination', $this);

	}

	/**
	 * get Page
	 *
	 * @version	29th June 2016
	 * @since	24th May 2016
	 * @return	integer		Page Number
	 */
	public function getPage()
	{
		return $this->get('page');
	}

	/**
	 * get 
	 *
	 * @version	29th June 2016
	 * @since	29th June 2016
	 * @param	string		$var	Variable
	 * @return	mixed		Variable Value
	 */
	public function get($var)
	{
		if (! isset($this->$var))
			return null;
		return $this->$var;
	}

	/**
	 * Generate Details
	 *
	 * @version	11th August 2016
	 * @since	29th June 2016
	 * @param	boolean		$recordOnly
	 * @return	array		recordInterface / Results Only
	 */
	public function generateDetails($recordOnly = true)
	{
		$sql = "SELECT COUNT(`".$this->record->getTableName()."`.`".$this->record->getIdentifierName()."`) FROM `" . $this->record->getTableName().'`';
		$sql .= ! empty($this->join) ? ' '.$this->join : '';
		$sql .= ! empty($this->where) ? ' 
	WHERE ' . $this->where : '';

		$x = $this->record->executeQuery($this->data, $sql, '_');
		if (! $this->record->getSuccess())
		{
			$this->view->displayMessage($this->record->getError());
			$this->total = 0;
			return null;
		}
		$this->total = $x->fetchColumn();

		$sql = 'SELECT ';
		$sql .= empty($this->select) ? '*' : $this->select ;
		$sql .= " FROM " . $this->record->getTableName();
		$sql .= ! empty($this->join) ? ' '.trim($this->join) : '';
		$sql .= ! empty($this->where) ? " WHERE " . $this->where : '';
		if (! empty($this->order))
		{
			$sql .= ' ORDER BY ';
			foreach($this->order as $field=>$order)
			{
				$sql .= '`'.$field.'` ' . $order . ',';
			}
			$sql = rtrim($sql, ',');
		}
		$sql .= ' LIMIT ' . intval(($this->page - 1) * $this->pagination) . ', ' . $this->pagination ;
		$this->results = $this->record->findAll($sql, $this->data);
		if (! $this->record->getSuccess())
		{
			$this->view->displayMessage($this->record->getError());
			$this->total = 0;
			return null;
		}
		if (! empty($this->results) && $recordOnly)
		{
			foreach($this->results as $q=>$w)
			{
				$this->results[$q] = $w->returnRecord();
			}
		}
		return $this->results;
	}
}
