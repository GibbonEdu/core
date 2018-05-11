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

namespace Gibbon\Tables;

use Gibbon\Tables\Action;
use Gibbon\Tables\Column;
use Gibbon\Domain\QueryResult;
use Gibbon\Tables\ActionColumn;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Tables\Renderer\RendererInterface;
use Gibbon\Tables\Renderer\SimpleRenderer;
use Gibbon\Tables\Renderer\PaginatedRenderer;

/**
 * DataTable
 *
 * @version v16
 * @since   v16
 */
class DataTable
{
    protected $id;
    protected $columns = array();
    protected $filters = array();
    protected $actionLinks = array();

    protected $queryResult;
    protected $renderer;

    public function __construct($id, QueryResult $queryResult, RendererInterface $renderer)
    {
        $this->id = $id;
        $this->queryResult = $queryResult;
        

        $this->renderer = $renderer;
    }

    public static function createSimpleTable($id, QueryResult $queryResult)
    {
        $renderer = new SimpleRenderer();
        return new DataTable($id, $queryResult, $renderer);
    }

    public static function createPaginatedTable($id, QueryResult $queryResult, QueryCriteria $criteria)
    {
        $renderer = new PaginatedRenderer($criteria);
        return new DataTable($id, $queryResult, $renderer);
    }

    public function getID()
    {
        return $this->id;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setPath($path = '')
    {
        $this->path = $path;

        return $this;
    }

    public function addColumn($name, $label = '')
    {
        $this->columns[$name] = new Column($name, $label);

        return $this->columns[$name];
    }

    public function addActionColumn()
    {
        $this->columns['actions'] = new ActionColumn();

        return $this->columns['actions'];
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function addHeaderAction($name, $label = '')
    {
        $this->actionLinks[$name] = new Action($name, $label);

        return $this->actionLinks[$name];
    }

    public function getHeaderActions()
    {
        return $this->actionLinks;
    }

    public function addFilter($name, $label = '')
    {
        $this->filters[$name] = $label;

        return $this;
    }

    public function addFilters($filters)
    {
        $this->filters = array_replace($this->filters, $filters);

        return $this;
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function getOutput()
    {
        return $this->renderer->renderTable($this, $this->queryResult);
    }

    
}