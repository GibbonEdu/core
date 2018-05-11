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

use Gibbon\Domain\QueryResult;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Tables\Action;
use Gibbon\Tables\Column;
use Gibbon\Tables\ActionColumn;
use Gibbon\Tables\Renderer\RendererInterface;
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
    protected $path;

    protected $columns = array();
    protected $filterOptions = array();
    protected $actionLinks = array();

    public function __construct($id, $path)
    {
        $this->id = $id;
        $this->path = $path;
    }

    public static function create($id)
    {
        return new static($id, '/fullscreen.php?q='.$_GET['q']);
    }

    public function getID()
    {
        return $this->id;
    }

    public function setPath($path = '')
    {
        $this->path = $path;

        return $this;
    }

    public function getPath()
    {
        return $this->path;
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

    public function addFilterOption($name, $label = '')
    {
        $this->filterOptions[$name] = $label;

        return $this;
    }

    public function addFilterOptions($filterOptions)
    {
        $this->filterOptions = array_replace($this->filterOptions, $filterOptions);

        return $this;
    }

    public function getFilterOptions()
    {
        return $this->filterOptions;
    }

    public function renderToHTML(QueryResult $queryResult, QueryCriteria $criteria)
    {
        $renderer = new PaginatedRenderer($criteria);
        return $renderer->renderTable($this, $queryResult);
    }

    public function renderWith(RendererInterface $renderer, QueryResult $queryResult)
    {
        return $renderer->renderTable($this, $queryResult);
    }
}