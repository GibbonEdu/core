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

namespace Gibbon\Forms\Traits;

use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\DataSet;

/**
 * MultipleOptions
 *
 * Adds functionaly for types of input that offer users multiple options. Methods are provided for reading options from a variety of sources.
 *
 * @version v14
 * @since   v14
 */
trait MultipleOptionsTrait
{
    protected $options = array();

    /**
     * Build an internal options array from a provided CSV string.
     * @param   string  $value
     * @return  self
     */
    public function fromString($value)
    {
        if (empty($values)) {
            $values = '';
        }

        if (!is_string($value)) {
            throw new \InvalidArgumentException(sprintf('Element %s: fromString expects value to be a string, %s given.', $this->getName(), gettype($value)));
        }

        if (!empty($value)) {
            $pieces = str_getcsv($value);

            foreach ($pieces as $piece) {
                $piece = trim($piece ?? '');
                $this->options[$piece] = $piece;
            }
        }

        return $this;
    }

    /**
     * Build an internal options array from a provided array of $key => $value pairs.
     * @param   array  $values
     * @return  self
     */
    public function fromArray($values, $valCol = null, $nameCol = null, $groupBy = false)
    {
        if (empty($values)) {
            $values = [];
        }

        if (!is_array($values)) {
            throw new \InvalidArgumentException(sprintf('Element %s: fromArray expects value to be an Array, %s given.', $this->getName(), gettype($values)));
        }

        if (!empty($valCol) && !empty($nameCol)) {
            // Extract named values from an associative array
            $this->setOptionsFromArray($values, $valCol, $nameCol, $groupBy);
        } elseif (array_values($values) === $values) {
            // Convert non-associative array and trim values
            foreach ($values as $value) {
                $this->options[trim(strval($value))] = !is_array($value)? trim($value ?? '') : $value;
            }
        } else {
            // Trim keys and values for associative array
            foreach ($values as $key => $value) {
                $this->options[trim($key ?? '')] = !is_array($value)? trim($value ?? '') : $value;
            }
        }

        return $this;
    }

    /**
     * Build an internal options array from an SQL query with required value and name fields
     * @param   Connection  $db
     * @param   string      $sql
     * @param   array      $data
     * @return  self
     */
    public function fromQuery(Connection $db, $sql, $data = [], $groupBy = false)
    {
        $results = $db->select($sql, $data);

        return $this->fromResults($results, $groupBy);
    }

    /**
     * Build options array from a DataSet, as provided by <b>Domain</b> gateways
     *
     * @param Dataset $dataset
     * @param string $valCol
     * @param string $nameCol
     * @param string $groupBy
     *
     * @return self
     */
    public function fromDataSet(Dataset $dataset, $valCol, $nameCol, $groupBy = false)
    {
        if(empty($dataset) || !is_object($dataset)){
            throw new \InvalidArgumentException(sprintf('Element %s: fromQuery expects value to be an Object, %s given.', $this->getName(), gettype($dataset)));
        }

        if($dataset->getTotalCount() > 0)
        {
            $this->setOptionsFromArray($dataset->toArray(), $valCol, $nameCol, $groupBy);
        }

        return $this;
    }

    /**
     * Build an internal options array from the result set of a PDO query.
     * @param   object  $results
     *
     * @return  self
     */
    public function fromResults($results, $groupBy = false)
    {
        if (empty($results) || !is_object($results)) {
            throw new \InvalidArgumentException(sprintf('Element %s: fromQuery expects value to be an Object, %s given.', $this->getName(), gettype($results)));
        }

        if ($results && $results->rowCount() > 0) {
            $this->setOptionsFromArray($results->fetchAll(), 'value', 'name', $groupBy);
        }

        return $this;
    }

    /**
     * Gets the internal options collection.
     * @return  array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Recursivly count the total options in the collection.
     * @return  int
     */
    public function getOptionCount()
    {
        return count($this->options, COUNT_RECURSIVE);
    }

    /**
     * Extract value => name options from an array containing multiple key => value pairs.
     *
     * @param array $values
     * @param string $valCol
     * @param string $nameCol
     * @param string $groupBy
     * @return void
     */
    protected function setOptionsFromArray(array $values, string $valCol, string $nameCol, $groupBy = null)
    {
        $options = array_filter($values, function ($item) use ($valCol,$nameCol) {
            return isset($item[$valCol]) && isset($item[$nameCol]);
        });

        foreach ($options as $option) {
            $option = is_array($option) ? 
                array_map(function ($item) {
                    return is_string($item) ? trim($item ?? '') : $item;
                }, $option) 
                : $option;

            if (!empty($groupBy)) {
                $this->options[$option[$groupBy]][$option[$valCol]] = __($option[$nameCol]);
            } else {
                $this->options[$option[$valCol]]= __($option[$nameCol]);
            }
        }
    }
}
