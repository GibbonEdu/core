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
            throw new InvalidArgumentException(sprintf('Element %s: fromString expects value to be a string, %s given.', $this->name, gettype($value)));
        }

        $pieces = str_getcsv($value);

        foreach ($pieces as $piece) {
            $piece = trim($piece);

            $this->options[$piece] = $piece;
        }

        return $this;
    }

    public function fromArray($value)
    {
        if (empty($value) || !is_array($value)) {
            throw new InvalidArgumentException(sprintf('Element %s: fromArray expects value to be an Array, %s given.', $this->name, gettype($value)));
        }

        $this->options = array_merge($this->options, $value);

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
            throw new InvalidArgumentException(sprintf('Element %s: fromQuery expects value to be an Object, %s given.', $this->name, gettype($results)));
        }

        if ($results && $results->rowCount() > 0) {
            while ($row = $results->fetch()) {
                if (!isset($row['value']) || !isset($row['name'])) {
                    continue;
                }

                $this->options[$row['value']] = $row['name'];
            }
        }

        return $this;
    }

    protected function getOptions()
    {
        return $this->options;
    }
}
