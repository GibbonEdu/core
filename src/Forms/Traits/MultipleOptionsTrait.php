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

namespace Gibbon\Forms\Traits;

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

    public function fromString($value)
    {
        if (empty($value) || !is_string($value)) {
            throw new \InvalidArgumentException(sprintf('Element %s: fromString expects value to be a string, %s given.', $this->getName(), gettype($value)));
        }

        $pieces = str_getcsv($value);

        foreach ($pieces as $piece) {
            $piece = trim($piece);
            $this->options[$piece] = $piece;
        }

        return $this;
    }

    public function fromArray($values)
    {
        if (empty($values) || !is_array($values)) {
            throw new \InvalidArgumentException(sprintf('Element %s: fromArray expects value to be an Array, %s given.', $this->getName(), gettype($values)));
        }

        if (array_values($values) === $values) {
            // Convert non-associative array and trim values
            foreach ($values as $value) {
                $this->options[trim(strval($value))] = (!is_array($value))? trim($value) : $value;
            }
        } else {
            // Trim keys and values for associative array
            foreach ($values as $key => $value) {
                $this->options[trim($key)] = (!is_array($value))? trim($value) : $value;
            }
        }

        return $this;
    }

    public function fromQuery(\Gibbon\sqlConnection $pdo, $sql, $data = array())
    {
        $results = $pdo->executeQuery($data, $sql);

        return $this->fromResults($results);
    }

    public function fromResults($results)
    {
        if (empty($results) || !is_object($results)) {
            throw new \InvalidArgumentException(sprintf('Element %s: fromQuery expects value to be an Object, %s given.', $this->getName(), gettype($results)));
        }

        if ($results && $results->rowCount() > 0) {
            while ($row = $results->fetch()) {
                if (!isset($row['value']) || !isset($row['name'])) {
                    continue;
                }

                $this->options[trim($row['value'])] = trim($row['name']);
            }
        }

        return $this;
    }

    protected function getOptions()
    {
        return $this->options;
    }

    protected function getOptionCount()
    {
        return count($this->options, COUNT_RECURSIVE);
    }
}
