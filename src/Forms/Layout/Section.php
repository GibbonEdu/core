<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

namespace Gibbon\Forms\Layout;

use Gibbon\Forms\FormFactoryInterface;
use Gibbon\Forms\OutputableInterface;
use Gibbon\Forms\Traits\BasicAttributesTrait;
use Gibbon\Forms\Traits\FormFieldsTrait;

/**
 * Section
 *
 * @version v30
 * @since   v30
 * 
 * {@inheritDoc}
 */
class Section
{
    use BasicAttributesTrait;
    use FormFieldsTrait;

    protected $factory;
    protected $heading;
    protected $open = true;
    
    protected $rows = [];

    /**
     * Add a layout section that contains one or more rows.
     * @param  string  $content
     */
    public function __construct(FormFactoryInterface $factory, $id, $heading = '')
    {
        $this->factory = $factory;
        $this->setID($id);
        $this->setHeading($heading);
    }

    public function getHeading()
    {
        return $this->heading;
    }

    public function setHeading($heading)
    {
        if (empty($this->heading) && !empty($heading)) {
            $this->addRow()->addHeading($heading, $heading);
        }

        $this->heading = $heading;
        return $this;
    }

    public function addElement(Element $element)
    {
        $this->getCurrentRow()->addElement($element);

        return $element;
    }

    /**
     * Adds a row to the section's internal collection.
     * @param  ?Row  $row
     */
    public function addRow(?Row $row = null)
    {
        if (empty($row)) {
            $row = $this->factory->createRow();
        }
        
        if (empty($row->getHeading())) {
            $row->setHeading($this->getHeading());
        }

        $id = $this->getUniqueIdentifier($row);

        $this->rows[$id] = $row;

        return $row;
    }

    /**
     * Set all rows from an array.
     *
     * @param array $rows
     * @return self
     */
    public function setRows(array $rows)
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * Get a row row by ID
     * @param   string  $id
     * @return  object Row
     */
    public function getRow(string $id = '')
    {
        if (empty($this->rows) || count($this->rows) == 0) {
            return null;
        }

        return $this->rows[$id] ?? null;
    }

    /**
     * Get an array of all row rows.
     * @return  array
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * Count the rows array.
     * @return  int
     */
    public function getRowCount()
    {
        return count($this->rows);
    }

    public function getCurrentRow()
    {
        if (empty($this->rows)) {
            $this->addRow();
        }

        return end($this->rows);
    }

    public function removeRow($id)
    {
        if (!empty($this->rows[$id])) {
            unset($this->rows[$id]);
        }

        return $this;
    }

    public function opened(bool $open)
    {
        $this->open = $open;

        return $this;
    }

    public function closed()
    {
        $this->open = false;

        return $this;
    }

    public function getOpen()
    {
        return $this->open;
    }

    /**
     * Pass an array of $key => $value pairs into each row in the collection.
     * @param   array  &$data
     * @return  self
     */
    public function loadFrom(&$data)
    {
        foreach ($this->getRows() as $row) {
            if (method_exists($row, 'loadFrom')) {
                $row->loadFrom($data);
            }
        }

        return $this;
    }

    /**
     * Load the state of several fields at once by calling $method on each row present in $data by key, passing in the value of $data.
     * @param string $method
     * @param array $data
     * @return self
     */
    public function loadState($method, $data)
    {
        foreach ($this->getRows() as $row) {
            $id = $this->getUniqueIdentifier($row);

            if (isset($data[$id]) && method_exists($row, $method)) {
                $row->$method($data[$id]);
            }
        }

        return $this;
    }

    /**
     * Gets the string identifier for an row that can be used as an array key.
     * @param Row $row
     * @return string
     */
    protected function getUniqueIdentifier(Row $row)
    {
        if (method_exists($row, 'getID') && !empty($row->getID())) {
            return $row->getID();
        }

        if (method_exists($row, 'getName') && !empty($row->getName())) {
            return $row->getName();
        }

        return $this->getID().'-'.$this->getRowCount();
    }
}
