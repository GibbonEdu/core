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

namespace Gibbon\Tables\Columns;

use Gibbon\Tables\DataTable;
use Gibbon\Forms\Input\Checkbox;

/**
 * DraggableColumn
 *
 * @version v16
 * @since   v16
 */
class DraggableColumn extends Column
{
    /**
     * Creates a pre-defined column for drag-drop row sorting.
     */
    public function __construct($id, $ajaxURL, array $data, DataTable $table)
    {
        parent::__construct('draggable');
        $this->sortable(false)
             ->context('action')
             ->width('3%; max-width: 1.8rem; min-width: 1.8rem;');

        $this->format(function ($values) use ($id) {
            return '<div class="drag-handle w-2 h-5 px-px border-4 border-dotted hover:border-gray-500 cursor-move"></div><input type="hidden" name="order[]" value="'.$values[$id].'">';
        });

        $table->addMetaData('draggable', ['url' => $ajaxURL, 'data' => json_encode($data)]);
    }

    /**
     * Overrides the label.
     * @return string
     */
    public function getLabel()
    {
        return '';
    }
}
