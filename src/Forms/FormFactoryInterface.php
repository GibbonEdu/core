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

namespace Gibbon\Forms;

use Gibbon\Forms\Layout\Column;
use Gibbon\Forms\Layout\Element;
use Gibbon\Forms\Layout\Row;
use Gibbon\Forms\Layout\Trigger;

interface FormFactoryInterface
{
    /**
     * Create form row.
     *
     * @param string $id  HTML id of the row.
     * @return \Gibbon\Forms\Layout\Row
     */
    public function createRow($id = ''): Row;

    /**
     * Create flex column.
     *
     * @param string $id  HTML id of the column.
     * @return \Gibbon\Forms\Layout\Column
     */
    public function createColumn($id = ''): Column;

    /**
     * Create javascript trigger for certain event of DOM object
     * specified by the selector.
     *
     * @param string $selector
     *
     * @return \Gibbon\Forms\Layout\Trigger
     */
    public function createTrigger($selector = ''): Trigger;

    /**
     * Create output element from given content.
     *
     * @param string $content
     *
     * @return \Gibbon\Forms\Layout\Element
     */
    public function createContent($content = ''): Element;

    /**
     * Create select element from the given name.
     *
     * @param string $name
     *
     * @return \Gibbon\Forms\Input\Select
     */
    public function createSelect($name);
}
