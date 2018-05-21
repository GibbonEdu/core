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

use Gibbon\Domain\DataSet;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Tables\Action;
use Gibbon\Tables\Column;
use Gibbon\Tables\ActionColumn;
use Gibbon\Tables\Renderer\PaginatedRenderer;
use Gibbon\Tables\Renderer\RendererInterface;
use Gibbon\Forms\OutputableInterface;

/**
 * DataTable
 *
 * @version v16
 * @since   v16
 */
class DataTable implements OutputableInterface
{
    protected $id;
    protected $data;
    protected $renderer;

    protected $columns = array();
    protected $header = array();
    protected $meta = array();

    protected $rowLogic;
    protected $cellLogic;

    /**
     * Create a data table with optional renderer.
     *
     * @param string $id
     * @param RendererInterface $renderer
     */
    public function __construct($id, RendererInterface $renderer = null)
    {
        $this->id = $id;
        $this->renderer = $renderer;
    }

    /**
     * Static create method, for ease of method chaining.
     *
     * @param string $id
     * @param RendererInterface $renderer
     * @return self
     */
    public static function create($id, RendererInterface $renderer = null)
    {
        return new self($id, $renderer);
    }

    /**
     * Helper method to create a default paginated data table, using criteria from a gateway query.
     *
     * @param string $id
     * @param QueryCriteria $criteria
     * @return self
     */
    public static function createPaginated($id, QueryCriteria $criteria)
    {
        return new self($id, new PaginatedRenderer($criteria, '/fullscreen.php?'.http_build_query($_GET)));
    }

    /**
     * Set the table ID.
     *
     * @param string $id
     * @return self
     */
    public function setID($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the table ID.
     *
     * @return string
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * Set the table data internally.
     *
     * @param DataSet $data
     * @return self
     */
    public function withData(DataSet $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set the renderer for the data table. Cal also be supplied ad hoc in the render method.
     *
     * @param RendererInterface $renderer
     * @return self
     */
    public function setRenderer(RendererInterface $renderer)
    {
        $this->renderer = $renderer;

        return $this;
    }

    /**
     * Get the current data table renderer.
     *
     * @return RendererInterface
     */
    public function getRenderer()
    {
        return $this->renderer;
    }

    /**
     * Add a column to the table, by name and optional label. Returns the created column.
     *
     * @param string $name
     * @param string $label
     * @return Column
     */
    public function addColumn($id, $label = '')
    {
        $this->columns[$id] = new Column($id, $label);

        return $this->columns[$id];
    }

    /**
     * Add an action column to the table, which is generally rendered on the right-hand side.
     *
     * @return ActionColumn
     */
    public function addActionColumn()
    {
        $this->columns['actions'] = new ActionColumn();

        return $this->columns['actions'];
    }

    /**
     * Get all columns in the table.
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Add an action to the table, generally displayed in the header right-hand side.
     *
     * @param string $name
     * @param string $label
     * @return Action
     */
    public function addHeaderAction($name, $label = '')
    {
        $this->header[$name] = new Action($name, $label);

        return $this->header[$name];
    }

    /**
     * Get all header content in the table.
     *
     * @return array
     */
    public function getHeader()
    {
        return $this->header;
    }


    /**
     * Add a piece of meta data to the table. Can be used for renderer-specific details.
     *
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function addMetaData($name, $value)
    {
        $this->meta[$name] = $value;

        return $this;
    }

    /**
     * Gets the value of a meta data entry by name.
     *
     * @param string $name
     * @return mixed
     */
    public function getMetaData($name, $defaultValue = null)
    {
        return isset($this->meta[$name]) ? $this->meta[$name] : $defaultValue;
    }

    /**
     * Set a callable function that can modify each row based on that row's data.
     *
     * @param callable $callable
     * @return self
     */
    public function setRowLogic(callable $callable)
    {
        $this->rowLogic = $callable;

        return $this;
    }

    /**
     * Get the row logic callable.
     *
     * @return callable
     */
    public function getRowLogic()
    {
        return $this->rowLogic;
    }

    /**
     * Set a callable function that can modify each cell based on that row's data.
     *
     * @param callable $callable
     * @return self
     */
    public function setCellLogic(callable $callable)
    {
        $this->cellLogic = $callable;

        return $this;
    }

    /**
     * Get the cell logic callable.
     *
     * @return callable
     */
    public function getCellLogic()
    {
        return $this->cellLogic;
    }

    /**
     * Render the data table, either with the supplied renderer or default to the built-in one.
     *
     * @param DataSet $dataSet
     * @param RendererInterface $renderer
     * @return string
     */
    public function render(DataSet $dataSet, RendererInterface $renderer = null)
    {
        $renderer = isset($renderer)? $renderer : $this->renderer;

        return $renderer->renderTable($this, $dataSet);
    }

    public function getOutput()
    {
        return $this->renderer->renderTable($this, $this->data);
    }
}
