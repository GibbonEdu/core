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

namespace Gibbon\Tables\Renderer;

use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\Columns\Column;
use Gibbon\Tables\Renderer\RendererInterface;

/**
 * JSON Renderer
 *
 * @version v17
 * @since   v17
 */
class JsonRenderer implements RendererInterface
{
    protected $jsonOptions;

    /**
     * @param int json_encode options (optional)
     */
    public function __construct($jsonOptions = JSON_PRETTY_PRINT)
    {
        $this->jsonOptions = $jsonOptions;
    }

    /**
     * Render the table to JSON.
     *
     * @param DataTable $table
     * @param DataSet $dataSet
     * @return string
     */
    public function renderTable(DataTable $table, DataSet $dataSet)
    {
        $jsonData = array();

        foreach ($dataSet as $index => $data) {
            $jsonData[$index] = $this->jsonifyColumns($table->getColumns(0), $data);
        }

        return json_encode($jsonData, $this->jsonOptions);
    }

    /**
     * Recursively build a set of column data, handling nested columns as nested JSON objects.
     *
     * @param array $columns
     * @param array $data
     * @return array
     */
    protected function jsonifyColumns(array $columns, array &$data)
    {
        $columnData = array();
        foreach ($columns as $column) {
            $columnData[$column->getID()] = ($column->getTotalDepth() > 1)
                ? $this->jsonifyColumns($column->getColumns(), $data)
                : $this->stripTags($column->getOutput($data));
        }
        return $columnData;
    }

    protected function stripTags($content)
    {
        $content = preg_replace('/\<br(\s*)?\/?\>/i', PHP_EOL, $content);
        return strip_tags($content);
    }
}
